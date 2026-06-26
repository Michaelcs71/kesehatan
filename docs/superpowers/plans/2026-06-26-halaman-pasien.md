# Halaman Pasien Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mengganti 4 halaman placeholder area pasien dengan 2 halaman fungsional (Jadwal CGD + Riwayat bertab) plus redirect & menu sidebar pasien, semua read-only kecuali konfirmasi pengingat MO yang memakai alur yang sudah ada.

**Architecture:** `PasienController` (tipis) memanggil `PasienRiwayatService` (statis) yang meng-query data milik pasien yang login via scope model yang sudah ada. View server-rendered + pagination Laravel. Route `pengingat-mo`/`pengingat-cgd` jadi redirect ke Riwayat dengan tab terpilih.

**Tech Stack:** Laravel 12, PHP 8.2, Eloquent, Carbon, Blade (layouts.app, Bootstrap 5/CoreUI/RemixIcon), PHPUnit (sqlite :memory:).

## Global Constraints

- Bahasa domain **Indonesian** untuk nama method, variabel, dan string UI.
- Service memakai **method statis** (ikuti konvensi codebase).
- Controller tipis: panggil service, `return view(...)`/`redirect(...)`.
- UUID primary keys.
- Semua query difilter berdasarkan id pasien yang login (`Auth::id()`), TIDAK menerima id dari request → pasien tidak boleh melihat data pasien lain.
- Semua route tetap di grup `['auth','verified','role:pasien']` yang sudah ada (`routes/web.php` ~baris 239–252).
- Relasi pasien–PMO via `PasienPmo` (`id_user` = pasien).
- Konfirmasi MO via route bernama `pengingat.konfirmasi.show` dengan param `kejadian` (UUID model `PengingatKejadian`); CGD tidak punya alur konfirmasi (read-only).
- Field jam disimpan `HH:MM:SS`; tampilkan `substr(...,0,5)`. `JadwalCgd.tgl_jadwal_cgd` & log `tgl_*` di-cast ke date (Carbon).
- Tests memakai `RefreshDatabase`; pasien dibuat `User::factory()->create(['role'=>'pasien','is_active'=>true])`.

---

## File Structure

- Create: `app/Services/PasienRiwayatService.php` — query read-only data pasien (jadwal CGD, riwayat MO/CGD, pending konfirmasi).
- Create: `app/Http/Controllers/PasienController.php` — 4 method (jadwalCgd, riwayat, pengingatMo, pengingatCgd).
- Create: `resources/views/pasien/jadwal-cgd.blade.php`, `resources/views/pasien/riwayat.blade.php`.
- Modify: `routes/web.php` (~244–251) — ganti 4 `Route::view` jadi action controller; tambah import.
- Modify: `resources/views/components/sidebar.blade.php` — tambah blok menu `@if($isPasien)`.
- Create tests: `tests/Feature/Pasien/PasienRiwayatServiceTest.php`, `PasienJadwalCgdTest.php`, `PasienRiwayatTest.php`, `PasienPengingatRedirectTest.php`.

---

## Task 1: PasienRiwayatService

**Files:**
- Create: `app/Services/PasienRiwayatService.php`
- Test: `tests/Feature/Pasien/PasienRiwayatServiceTest.php`

**Interfaces:**
- Produces:
  - `PasienRiwayatService::jadwalCgdPasien(string $userId): array` → `['mendatang' => array, 'lewat' => array]`, tiap item `['tanggal'=>Carbon,'jam'=>string,'tempat'=>?string,'puasa'=>?string,'status'=>?string]`.
  - `PasienRiwayatService::riwayatMo(string $userId, array $filter = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator`
  - `PasienRiwayatService::riwayatCgd(string $userId, array $filter = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator`
  - `PasienRiwayatService::pendingKonfirmasi(string $userId): \Illuminate\Support\Collection` (model `PengingatKejadian`)

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Pasien/PasienRiwayatServiceTest.php`:

```php
<?php

namespace Tests\Feature\Pasien;

use App\Models\JadwalCgd;
use App\Models\JadwalCgdPeserta;
use App\Models\PasienPmo;
use App\Models\PengingatCgdLog;
use App\Models\PengingatKejadian;
use App\Models\PengingatMoLog;
use App\Models\User;
use App\Services\PasienRiwayatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasienRiwayatServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pasien(): User
    {
        return User::factory()->create(['role' => 'pasien', 'is_active' => true]);
    }

    private function pasienPmoUntuk(User $p): PasienPmo
    {
        return PasienPmo::create([
            'id_user' => $p->id, 'pmo_user_id' => null,
            'nama_pasien' => $p->name, 'nik' => fake()->numerify('################'),
            'nama_pmo' => '-', 'jenis_pmo' => 'Keluarga',
            'tanggal_regis' => now()->toDateString(), 'status_diabetes' => 'Tipe 2',
            'is_active' => true,
        ]);
    }

    public function test_jadwal_cgd_memisah_mendatang_dan_lewat(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-26 10:00:00'));
        $p = $this->pasien();
        $pp = $this->pasienPmoUntuk($p);

        $besok = JadwalCgd::factory()->create(['tgl_jadwal_cgd' => '2026-06-27', 'jam_mulai' => '08:00:00']);
        $kemarin = JadwalCgd::factory()->create(['tgl_jadwal_cgd' => '2026-06-25', 'jam_mulai' => '09:00:00']);
        foreach ([$besok, $kemarin] as $j) {
            JadwalCgdPeserta::create([
                'jadwal_cgd_id' => $j->id, 'id_pasien_pmo' => $pp->id,
                'nama_pasien' => $p->name, 'nama_pmo' => '-',
            ]);
        }

        $hasil = PasienRiwayatService::jadwalCgdPasien($p->id);

        $this->assertCount(1, $hasil['mendatang']);
        $this->assertCount(1, $hasil['lewat']);
        $this->assertSame('08:00', $hasil['mendatang'][0]['jam']);
    }

    public function test_riwayat_mo_hanya_milik_pasien(): void
    {
        $p = $this->pasien();
        $lain = $this->pasien();
        PengingatMoLog::create([
            'id_jo' => JadwalMinumObatId(), 'id_user' => $p->id, 'nama_pasien' => $p->name,
            'nama_obat' => 'Metformin', 'tgl_minum_obat' => now()->toDateString(),
            'jam_minum_obat' => '08:00:00', 'jam_slot_target' => '08:00:00',
            'patuh_menit' => 0, 'foto_obat' => 'x.jpg', 'status' => 'aktif',
        ]);
        PengingatMoLog::create([
            'id_jo' => JadwalMinumObatId(), 'id_user' => $lain->id, 'nama_pasien' => $lain->name,
            'nama_obat' => 'Lain', 'tgl_minum_obat' => now()->toDateString(),
            'jam_minum_obat' => '08:00:00', 'jam_slot_target' => '08:00:00',
            'patuh_menit' => 0, 'foto_obat' => 'x.jpg', 'status' => 'aktif',
        ]);

        $page = PasienRiwayatService::riwayatMo($p->id);

        $this->assertSame(1, $page->total());
        $this->assertSame($p->id, $page->first()->id_user);
    }

    public function test_pending_konfirmasi_hanya_kejadian_mo_menunggu_milik_pasien(): void
    {
        $p = $this->pasien();
        PengingatKejadian::create([
            'jenis' => 'mo', 'jadwal_id' => Str::uuid()->toString(), 'id_pasien_pmo' => null,
            'user_pasien_id' => $p->id, 'user_pmo_id' => null,
            'waktu_jadwal' => now(), 'status' => PengingatKejadian::STATUS_MENUNGGU,
        ]);
        PengingatKejadian::create([
            'jenis' => 'mo', 'jadwal_id' => Str::uuid()->toString(), 'id_pasien_pmo' => null,
            'user_pasien_id' => $p->id, 'user_pmo_id' => null,
            'waktu_jadwal' => now(), 'status' => PengingatKejadian::STATUS_DIKONFIRMASI,
        ]);

        $this->assertCount(1, PasienRiwayatService::pendingKonfirmasi($p->id));
    }
}

// Helper: id_jo NOT NULL — buat jadwal MO via factory dan kembalikan id-nya.
function JadwalMinumObatId(): string
{
    return \App\Models\JadwalMinumObat::factory()->create()->id;
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PasienRiwayatServiceTest`
Expected: FAIL dengan "Class App\Services\PasienRiwayatService not found".

- [ ] **Step 3: Implement the service**

Create `app/Services/PasienRiwayatService.php`:

```php
<?php

namespace App\Services;

use App\Models\JadwalCgdPeserta;
use App\Models\PengingatCgdLog;
use App\Models\PengingatKejadian;
use App\Models\PengingatMoLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PasienRiwayatService
{
    public static function jadwalCgdPasien(string $userId): array
    {
        $peserta = JadwalCgdPeserta::query()
            ->whereHas('pasienPmo', fn ($q) => $q->where('id_user', $userId))
            ->with('jadwalCgd')
            ->get()
            ->filter(fn ($p) => $p->jadwalCgd !== null);

        $today = Carbon::today()->toDateString();

        $map = fn ($p) => [
            'tanggal' => $p->jadwalCgd->tgl_jadwal_cgd,
            'jam' => substr((string) $p->jadwalCgd->jam_mulai, 0, 5),
            'tempat' => $p->jadwalCgd->tempat,
            'puasa' => $p->jadwalCgd->puasa,
            'status' => $p->jadwalCgd->status,
        ];

        $mendatang = $peserta
            ->filter(fn ($p) => $p->jadwalCgd->tgl_jadwal_cgd->toDateString() >= $today)
            ->sortBy(fn ($p) => $p->jadwalCgd->tgl_jadwal_cgd->toDateString())
            ->map($map)->values()->all();

        $lewat = $peserta
            ->filter(fn ($p) => $p->jadwalCgd->tgl_jadwal_cgd->toDateString() < $today)
            ->sortByDesc(fn ($p) => $p->jadwalCgd->tgl_jadwal_cgd->toDateString())
            ->map($map)->values()->all();

        return ['mendatang' => $mendatang, 'lewat' => $lewat];
    }

    public static function riwayatMo(string $userId, array $filter = []): LengthAwarePaginator
    {
        return PengingatMoLog::query()->forUser($userId)
            ->when($filter['dari'] ?? null, fn ($q, $d) => $q->where('tgl_minum_obat', '>=', $d))
            ->when($filter['sampai'] ?? null, fn ($q, $d) => $q->where('tgl_minum_obat', '<=', $d))
            ->orderByDesc('tgl_minum_obat')->orderByDesc('jam_minum_obat')
            ->paginate(15)->withQueryString();
    }

    public static function riwayatCgd(string $userId, array $filter = []): LengthAwarePaginator
    {
        return PengingatCgdLog::query()->forUser($userId)
            ->when($filter['dari'] ?? null, fn ($q, $d) => $q->where('tgl_cgd', '>=', $d))
            ->when($filter['sampai'] ?? null, fn ($q, $d) => $q->where('tgl_cgd', '<=', $d))
            ->orderByDesc('tgl_cgd')->orderByDesc('jam_cgd')
            ->paginate(15)->withQueryString();
    }

    public static function pendingKonfirmasi(string $userId): Collection
    {
        return PengingatKejadian::query()
            ->where('user_pasien_id', $userId)
            ->where('jenis', 'mo')
            ->menunggu()
            ->orderBy('waktu_jadwal')
            ->get();
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=PasienRiwayatServiceTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint app/Services/PasienRiwayatService.php
git add app/Services/PasienRiwayatService.php tests/Feature/Pasien/PasienRiwayatServiceTest.php
git commit -m "feat(pasien): service riwayat & jadwal CGD pasien"
```

---

## Task 2: Halaman Jadwal CGD pasien + menu sidebar

**Files:**
- Create: `app/Http/Controllers/PasienController.php`
- Create: `resources/views/pasien/jadwal-cgd.blade.php`
- Modify: `routes/web.php` (~244 — route `pasien.jadwal.cgd`; tambah import)
- Modify: `resources/views/components/sidebar.blade.php` (tambah blok `@if($isPasien)`)
- Test: `tests/Feature/Pasien/PasienJadwalCgdTest.php`

**Interfaces:**
- Consumes: `PasienRiwayatService::jadwalCgdPasien()` (Task 1).
- Produces: `PasienController::jadwalCgd(): \Illuminate\View\View`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Pasien/PasienJadwalCgdTest.php`:

```php
<?php

namespace Tests\Feature\Pasien;

use App\Models\JadwalCgd;
use App\Models\JadwalCgdPeserta;
use App\Models\PasienPmo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasienJadwalCgdTest extends TestCase
{
    use RefreshDatabase;

    public function test_pasien_melihat_jadwal_cgd_miliknya(): void
    {
        $p = User::factory()->create(['role' => 'pasien', 'is_active' => true]);
        $pp = PasienPmo::create([
            'id_user' => $p->id, 'pmo_user_id' => null, 'nama_pasien' => $p->name,
            'nik' => fake()->numerify('################'), 'nama_pmo' => '-',
            'jenis_pmo' => 'Keluarga', 'tanggal_regis' => now()->toDateString(),
            'status_diabetes' => 'Tipe 2', 'is_active' => true,
        ]);
        $j = JadwalCgd::factory()->create(['tempat' => 'Puskesmas Melati']);
        JadwalCgdPeserta::create([
            'jadwal_cgd_id' => $j->id, 'id_pasien_pmo' => $pp->id,
            'nama_pasien' => $p->name, 'nama_pmo' => '-',
        ]);

        $this->actingAs($p)->get('/pasien/jadwal-cgd')
            ->assertOk()
            ->assertSee('Puskesmas Melati');
    }

    public function test_non_pasien_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->get('/pasien/jadwal-cgd')->assertForbidden();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PasienJadwalCgdTest`
Expected: FAIL (route masih `Route::view('placeholder')`, tidak ada "Puskesmas Melati").

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/PasienController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Services\PasienRiwayatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PasienController extends Controller
{
    public function jadwalCgd(): View
    {
        return view('pasien.jadwal-cgd', PasienRiwayatService::jadwalCgdPasien(Auth::id()));
    }

    public function riwayat(Request $request): View
    {
        $tab = in_array($request->query('tab'), ['obat', 'gula'], true)
            ? $request->query('tab') : 'obat';

        $filter = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
        ]);

        $userId = Auth::id();

        return view('pasien.riwayat', [
            'tab' => $tab,
            'pending' => PasienRiwayatService::pendingKonfirmasi($userId),
            'riwayatMo' => $tab === 'obat' ? PasienRiwayatService::riwayatMo($userId, $filter) : null,
            'riwayatCgd' => $tab === 'gula' ? PasienRiwayatService::riwayatCgd($userId, $filter) : null,
        ]);
    }

    public function pengingatMo(): RedirectResponse
    {
        return redirect()->route('pasien.riwayat', ['tab' => 'obat']);
    }

    public function pengingatCgd(): RedirectResponse
    {
        return redirect()->route('pasien.riwayat', ['tab' => 'gula']);
    }
}
```

- [ ] **Step 4: Wire the jadwal-cgd route**

Di `routes/web.php`, tambahkan import (dekat `use App\Http\Controllers\DashboardController;`):

```php
use App\Http\Controllers\PasienController;
```

Ganti baris route `pasien.jadwal.cgd` (~244–245) menjadi:

```php
Route::get('/jadwal-cgd', [PasienController::class, 'jadwalCgd'])->name('jadwal.cgd');
```

(Jangan ubah route pengingat-mo/cgd/riwayat di task ini — itu Task 3.)

- [ ] **Step 5: Create the blade**

Create `resources/views/pasien/jadwal-cgd.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Jadwal Cek Gula Darah')

@section('page-header')
    <h4 class="fw-bold mb-1">🩸 Jadwal Cek Gula Darah</h4>
    <small class="text-muted">Jadwal pemeriksaan gula darah Anda.</small>
@endsection

@section('content')
    @php
        $renderItem = function ($item) {
            return $item;
        };
    @endphp

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Mendatang</div>
        <div class="list-group list-group-flush">
            @forelse ($mendatang as $item)
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold">{{ $item['tanggal']->translatedFormat('l, d F Y') }}</div>
                        <small class="text-muted">
                            Pukul {{ $item['jam'] }} @if($item['tempat']) • {{ $item['tempat'] }} @endif
                        </small>
                    </div>
                    <span class="badge {{ ($item['puasa'] ?? '') === 'Wajib' ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary' }}">
                        {{ ($item['puasa'] ?? '') === 'Wajib' ? 'Puasa wajib' : 'Tidak perlu puasa' }}
                    </span>
                </div>
            @empty
                <div class="list-group-item text-muted text-center py-4">Belum ada jadwal mendatang.</div>
            @endforelse
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Selesai / Lewat</div>
        <div class="list-group list-group-flush">
            @forelse ($lewat as $item)
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold">{{ $item['tanggal']->translatedFormat('l, d F Y') }}</div>
                        <small class="text-muted">
                            Pukul {{ $item['jam'] }} @if($item['tempat']) • {{ $item['tempat'] }} @endif
                        </small>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-muted text-center py-4">Belum ada jadwal yang lewat.</div>
            @endforelse
        </div>
    </div>
@endsection
```

- [ ] **Step 6: Add pasien menu to sidebar**

Di `resources/views/components/sidebar.blade.php`, sebelum blok `{{-- ============ AKUN ============ --}}` (sekitar baris 172), tambahkan:

```blade
    {{-- ============ MENU PASIEN ============ --}}
    @if($isPasien)
        <li class="nav-title">Menu Saya</li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('pasien.jadwal.cgd') ? 'active' : '' }}"
                href="{{ route('pasien.jadwal.cgd') }}">
                <span class="nav-icon"><i class="ri ri-drop-line"></i></span> Jadwal Cek Gula Darah
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('pasien.riwayat') ? 'active' : '' }}"
                href="{{ route('pasien.riwayat') }}">
                <span class="nav-icon"><i class="ri ri-history-line"></i></span> Riwayat
            </a>
        </li>
    @endif
```

> `$isPasien` sudah didefinisikan di atas file sidebar (baris 4). Konfirmasi variabelnya ada sebelum memakai.

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test --filter=PasienJadwalCgdTest`
Expected: PASS (2 tests). `assertOk()` memastikan blade & sidebar dirender tanpa variabel undefined.

- [ ] **Step 8: Format & commit**

```bash
vendor/bin/pint app/Http/Controllers/PasienController.php
git add app/Http/Controllers/PasienController.php resources/views/pasien/jadwal-cgd.blade.php resources/views/components/sidebar.blade.php routes/web.php tests/Feature/Pasien/PasienJadwalCgdTest.php
git commit -m "feat(pasien): halaman jadwal CGD + menu sidebar pasien"
```

---

## Task 3: Halaman Riwayat (tab Obat/Gula + banner konfirmasi) + redirect pengingat

**Files:**
- Create: `resources/views/pasien/riwayat.blade.php`
- Modify: `routes/web.php` (~246–251 — route `pasien.pengingat.mo`, `pasien.pengingat.cgd`, `pasien.riwayat`)
- Test: `tests/Feature/Pasien/PasienRiwayatTest.php`, `tests/Feature/Pasien/PasienPengingatRedirectTest.php`

**Interfaces:**
- Consumes: `PasienController::riwayat/pengingatMo/pengingatCgd` (Task 2), `PasienRiwayatService::riwayatMo/riwayatCgd/pendingKonfirmasi` (Task 1).

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Pasien/PasienRiwayatTest.php`:

```php
<?php

namespace Tests\Feature\Pasien;

use App\Models\PengingatCgdLog;
use App\Models\PengingatKejadian;
use App\Models\PengingatMoLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasienRiwayatTest extends TestCase
{
    use RefreshDatabase;

    private function pasien(): User
    {
        return User::factory()->create(['role' => 'pasien', 'is_active' => true]);
    }

    public function test_tab_obat_menampilkan_log_mo_pasien(): void
    {
        $p = $this->pasien();
        PengingatMoLog::create([
            'id_jo' => \App\Models\JadwalMinumObat::factory()->create()->id,
            'id_user' => $p->id, 'nama_pasien' => $p->name, 'nama_obat' => 'Metformin500',
            'tgl_minum_obat' => now()->toDateString(), 'jam_minum_obat' => '08:00:00',
            'jam_slot_target' => '08:00:00', 'patuh_menit' => 0, 'foto_obat' => 'x.jpg', 'status' => 'aktif',
        ]);

        $this->actingAs($p)->get('/pasien/riwayat?tab=obat')
            ->assertOk()->assertSee('Metformin500');
    }

    public function test_tab_gula_menampilkan_log_cgd_pasien(): void
    {
        $p = $this->pasien();
        PengingatCgdLog::create([
            'id_cgd' => \App\Models\JadwalCgd::factory()->create()->id,
            'id_user' => $p->id, 'nama_pasien' => $p->name, 'jenis_kelamin' => 'L',
            'tempat_cgd' => 'Klinik X', 'tgl_cgd' => now()->toDateString(), 'jam_cgd' => '07:00:00',
            'hasil_mgdl' => 123, 'kategori_hasil' => 'normal', 'patuh_selisih' => -77,
            'foto_layar' => 'y.jpg', 'status' => 'aktif',
        ]);

        $this->actingAs($p)->get('/pasien/riwayat?tab=gula')
            ->assertOk()->assertSee('123');
    }

    public function test_banner_konfirmasi_muncul_dan_menaut_konfirmasi(): void
    {
        $p = $this->pasien();
        $k = PengingatKejadian::create([
            'jenis' => 'mo', 'jadwal_id' => Str::uuid()->toString(), 'id_pasien_pmo' => null,
            'user_pasien_id' => $p->id, 'user_pmo_id' => null,
            'waktu_jadwal' => now(), 'status' => PengingatKejadian::STATUS_MENUNGGU,
        ]);

        $this->actingAs($p)->get('/pasien/riwayat')
            ->assertOk()
            ->assertSee(route('pengingat.konfirmasi.show', $k->id), false);
    }

    public function test_non_pasien_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->get('/pasien/riwayat')->assertForbidden();
    }
}
```

Create `tests/Feature/Pasien/PasienPengingatRedirectTest.php`:

```php
<?php

namespace Tests\Feature\Pasien;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasienPengingatRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengingat_mo_redirect_ke_riwayat_tab_obat(): void
    {
        $p = User::factory()->create(['role' => 'pasien', 'is_active' => true]);
        $this->actingAs($p)->get('/pasien/pengingat-mo')
            ->assertRedirect(route('pasien.riwayat', ['tab' => 'obat']));
    }

    public function test_pengingat_cgd_redirect_ke_riwayat_tab_gula(): void
    {
        $p = User::factory()->create(['role' => 'pasien', 'is_active' => true]);
        $this->actingAs($p)->get('/pasien/pengingat-cgd')
            ->assertRedirect(route('pasien.riwayat', ['tab' => 'gula']));
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=PasienRiwayatTest && php artisan test --filter=PasienPengingatRedirectTest`
Expected: FAIL (route masih placeholder/`Route::view`; tidak ada view `pasien.riwayat`).

- [ ] **Step 3: Wire the routes**

Di `routes/web.php`, ganti tiga route placeholder (`pasien.pengingat.mo`, `pasien.pengingat.cgd`, `pasien.riwayat`, ~246–251) menjadi:

```php
        Route::get('/pengingat-mo', [PasienController::class, 'pengingatMo'])->name('pengingat.mo');
        Route::get('/pengingat-cgd', [PasienController::class, 'pengingatCgd'])->name('pengingat.cgd');
        Route::get('/riwayat', [PasienController::class, 'riwayat'])->name('riwayat');
```

- [ ] **Step 4: Create the blade**

Create `resources/views/pasien/riwayat.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Riwayat')

@section('page-header')
    <h4 class="fw-bold mb-1">📋 Riwayat</h4>
    <small class="text-muted">Riwayat minum obat & cek gula darah Anda.</small>
@endsection

@section('content')
    {{-- Banner konfirmasi pending (MO) --}}
    @if($pending->isNotEmpty())
        <div class="card border-0 shadow-sm mb-4 border-start border-warning border-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">⏰ Perlu Konfirmasi</h6>
                <div class="list-group list-group-flush">
                    @foreach($pending as $k)
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <div class="fw-semibold">Minum Obat</div>
                                <small class="text-muted">Jadwal pukul {{ $k->waktu_jadwal->format('H:i') }}, {{ $k->waktu_jadwal->translatedFormat('d M Y') }}</small>
                            </div>
                            <a href="{{ route('pengingat.konfirmasi.show', $k->id) }}" class="btn btn-sm btn-warning">
                                Konfirmasi
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'obat' ? 'active' : '' }}" href="{{ route('pasien.riwayat', ['tab' => 'obat']) }}">Minum Obat</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'gula' ? 'active' : '' }}" href="{{ route('pasien.riwayat', ['tab' => 'gula']) }}">Cek Gula Darah</a>
        </li>
    </ul>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                @if($tab === 'obat')
                    <thead><tr><th>Tanggal</th><th>Jam</th><th>Obat</th><th>Ketepatan</th></tr></thead>
                    <tbody>
                        @forelse($riwayatMo as $log)
                            <tr>
                                <td>{{ $log->tgl_minum_obat->translatedFormat('d M Y') }}</td>
                                <td>{{ $log->jam_minum_obat_format }}</td>
                                <td>{{ $log->nama_obat }}</td>
                                <td><span class="badge bg-{{ $log->patuh_badge_color }}-subtle text-{{ $log->patuh_badge_color }}">{{ $log->patuh_label }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada riwayat minum obat.</td></tr>
                        @endforelse
                    </tbody>
                @else
                    <thead><tr><th>Tanggal</th><th>Jam</th><th>Hasil (mg/dL)</th><th>Kategori</th><th>Tempat</th></tr></thead>
                    <tbody>
                        @forelse($riwayatCgd as $log)
                            <tr>
                                <td>{{ $log->tgl_cgd->translatedFormat('d M Y') }}</td>
                                <td>{{ $log->jam_cgd_format }}</td>
                                <td class="fw-semibold">{{ $log->hasil_mgdl }}</td>
                                <td><span class="badge bg-{{ $log->kategori_color }}-subtle text-{{ $log->kategori_color }}">{{ $log->kategori_label }}</span></td>
                                <td>{{ $log->tempat_cgd }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada riwayat cek gula darah.</td></tr>
                        @endforelse
                    </tbody>
                @endif
            </table>
        </div>
        <div class="card-footer bg-white">
            @if($tab === 'obat' && $riwayatMo)
                {{ $riwayatMo->links() }}
            @elseif($tab === 'gula' && $riwayatCgd)
                {{ $riwayatCgd->links() }}
            @endif
        </div>
    </div>
@endsection
```

> Catatan: `$log->patuh_badge_color` & `$log->kategori_color` adalah accessor yang sudah ada di model (`PengingatMoLog`/`PengingatCgdLog`). Pagination memakai Bootstrap; jika proyek belum memanggil `Paginator::useBootstrapFive()`, cek `App\Providers\AppServiceProvider` — jika belum ada, tambahkan `\Illuminate\Pagination\Paginator::useBootstrapFive();` di `boot()` (hanya bila links tampil tidak rapi; tidak wajib untuk test lulus).

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter=PasienRiwayatTest && php artisan test --filter=PasienPengingatRedirectTest`
Expected: PASS (4 + 2 tests).

- [ ] **Step 6: Run full Pasien suite + format**

Run: `php artisan test --filter=Pasien`
Expected: semua test Pasien hijau.

```bash
vendor/bin/pint
git add resources/views/pasien/riwayat.blade.php routes/web.php tests/Feature/Pasien/PasienRiwayatTest.php tests/Feature/Pasien/PasienPengingatRedirectTest.php
git commit -m "feat(pasien): halaman riwayat bertab + banner konfirmasi + redirect pengingat"
```

---

## Self-Review (sudah dijalankan penulis plan)

**Spec coverage:** Jadwal CGD (Task 2), Riwayat bertab + banner konfirmasi (Task 3), redirect pengingat-mo/cgd (Task 3), menu sidebar pasien (Task 2), service + isolasi data (Task 1), pengujian (tiap task). Semua bagian spec tercakup.

**Placeholder scan:** Tidak ada TODO/TBD pada langkah kode. Catatan "cek AppServiceProvider untuk Bootstrap paginator" adalah instruksi verifikasi konkret opsional, bukan placeholder kerja.

**Type consistency:** Nama method service konsisten antar task (`jadwalCgdPasien`, `riwayatMo`, `riwayatCgd`, `pendingKonfirmasi`); kunci array jadwal (`tanggal/jam/tempat/puasa/status`) konsisten antara service (Task 1) dan blade (Task 2); nama route (`pasien.jadwal.cgd`, `pasien.riwayat`, `pengingat.konfirmasi.show`) konsisten; accessor model (`patuh_badge_color`, `patuh_label`, `kategori_color`, `kategori_label`, `jam_minum_obat_format`, `jam_cgd_format`) sesuai yang ada di model.

## Catatan verifikasi saat eksekusi

- Pastikan `JadwalCgdPeserta::pasienPmo` & `JadwalCgd` cast `tgl_jadwal_cgd` → date sudah seperti diasumsikan (sudah dikonfirmasi di model).
- `User` factory tidak men-set `role`/`is_active`; test menyetelnya eksplisit.
- Layout `layouts.app` menyediakan section `title`, `page-header`, `content` (sama seperti `placeholder.blade.php`).
