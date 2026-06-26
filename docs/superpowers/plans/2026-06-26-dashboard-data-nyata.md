# Dashboard Data Nyata Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mengganti seluruh data dummy di 4 dashboard (Pasien, PMO, Admin, Superadmin) dengan query nyata melalui lapisan Controller → Service → Repository, dengan empty-state untuk widget historis.

**Architecture:** `DashboardController` (4 method tipis) menggantikan `Route::view()`. `DashboardService` (statis) menghitung view-model per role dan memanggil `DashboardRepository` (statis) untuk query agregat. Blade tetap memakai nama variabel lokal yang sama; hanya blok `@php` dummy yang diganti dengan pembacaan dari `$data` yang dikirim controller, sehingga markup 900-baris tidak perlu ditulis ulang.

**Tech Stack:** Laravel 12, PHP 8.2, Eloquent, Carbon, PHPUnit (sqlite :memory:), Blade, Chart.js (server-rendered JSON via `@json`).

## Global Constraints

- Bahasa domain **Indonesian** untuk nama method, variabel, dan string UI.
- Service & Repository memakai **method statis** (ikuti konvensi codebase).
- Controller tipis: hanya memanggil service dan `return view(...)`.
- UUID primary keys; jangan asumsikan integer id.
- Relasi pasien–PMO memakai model `PasienPmo` (`id_user` = pasien, `pmo_user_id` = PMO), **bukan** `PasienProfile`.
- Kepatuhan: persen log MO 30 hari dengan `patuh_kategori = 'tepat_waktu'` (selisih ≤15 menit); penyebut 0 → `0`.
- Streak: hari berturut-turut sampai hari ini tanpa `PengingatKejadian` berstatus `terlewat`; tanpa riwayat → `0`.
- Format jam disimpan `HH:MM:SS`; tampilkan `substr(...,0,5)`.
- Test memakai `RefreshDatabase`; user dibuat `User::factory()->create(['role' => '<role>', 'is_active' => true])`.

---

## File Structure

- Create: `app/Http/Controllers/DashboardController.php` — 4 method (`pasien`, `pmo`, `admin`, `superadmin`), masing-masing `return view('dashboard.<x>', $data)`.
- Create: `app/Services/DashboardService.php` — `untukPasien(User)`, `untukPmo(User)`, `untukAdmin(string $role)`; hitung kepatuhan & streak.
- Create: `app/Repos/DashboardRepository.php` — query agregat statis.
- Modify: `routes/web.php` — ganti 4 `Route::view('/dashboard', ...)` jadi `[DashboardController::class, '<method>']`.
- Modify: `resources/views/dashboard/{pasien,pmo,admin,superadmin}.blade.php` — ganti blok `@php` dummy + tambah empty-state.
- Create tests: `tests/Feature/Dashboard/DashboardPasienTest.php`, `DashboardPmoTest.php`, `DashboardAdminTest.php`.
- Create: `tests/Unit/Dashboard/DashboardServiceTest.php`.

---

## Task 1: DashboardService — metrik Pasien + Repository inti

**Files:**
- Create: `app/Repos/DashboardRepository.php`
- Create: `app/Services/DashboardService.php`
- Test: `tests/Unit/Dashboard/DashboardServiceTest.php`

**Interfaces:**
- Produces:
  - `DashboardRepository::hitungKepatuhanMo(string $pasienId, int $hari = 30): int` — persen 0–100.
  - `DashboardRepository::hitungStreak(string $pasienId): int`
  - `DashboardRepository::kejadianMoHariIni(string $pasienId): array` — `['total' => int, 'selesai' => int]`
  - `DashboardRepository::cgdHariIni(string $pasienId): array` — `['total' => int, 'selesai' => int]`
  - `DashboardRepository::trenGdPasien(string $pasienId, int $limit = 14): array` — list `['tgl' => 'Y-m-d', 'hasil' => int]`
  - `DashboardService::untukPasien(\App\Models\User $pasien): array`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Dashboard/DashboardServiceTest.php`:

```php
<?php

namespace Tests\Unit\Dashboard;

use App\Models\PengingatKejadian;
use App\Models\PengingatMoLog;
use App\Models\User;
use App\Repos\DashboardRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
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

    public function test_kepatuhan_menghitung_persen_tepat_waktu(): void
    {
        $p = $this->pasien();
        // 3 tepat (<=15), 1 telat (>15) => 75%
        foreach ([5, -10, 0] as $menit) {
            PengingatMoLog::create([
                'id_user' => $p->id, 'nama_pasien' => 'A', 'nama_obat' => 'O',
                'tgl_minum_obat' => now()->toDateString(), 'jam_minum_obat' => '08:00:00',
                'jam_slot_target' => '08:00:00', 'patuh_menit' => $menit, 'status' => 'aktif',
            ]);
        }
        PengingatMoLog::create([
            'id_user' => $p->id, 'nama_pasien' => 'A', 'nama_obat' => 'O',
            'tgl_minum_obat' => now()->toDateString(), 'jam_minum_obat' => '09:00:00',
            'jam_slot_target' => '08:00:00', 'patuh_menit' => 60, 'status' => 'aktif',
        ]);

        $this->assertSame(75, DashboardRepository::hitungKepatuhanMo($p->id));
    }

    public function test_kepatuhan_nol_saat_belum_ada_log(): void
    {
        $this->assertSame(0, DashboardRepository::hitungKepatuhanMo($this->pasien()->id));
    }

    public function test_streak_terputus_oleh_terlewat(): void
    {
        $p = $this->pasien();
        Carbon::setTestNow(Carbon::parse('2026-06-26 10:00:00'));
        // hari ini & kemarin tidak terlewat, lusa terlewat => streak 2
        PengingatKejadian::create(['jenis' => 'mo', 'jadwal_id' => null, 'id_pasien_pmo' => null,
            'user_pasien_id' => $p->id, 'user_pmo_id' => null,
            'waktu_jadwal' => '2026-06-26 08:00:00', 'status' => 'dikonfirmasi']);
        PengingatKejadian::create(['jenis' => 'mo', 'jadwal_id' => null, 'id_pasien_pmo' => null,
            'user_pasien_id' => $p->id, 'user_pmo_id' => null,
            'waktu_jadwal' => '2026-06-25 08:00:00', 'status' => 'dikonfirmasi']);
        PengingatKejadian::create(['jenis' => 'mo', 'jadwal_id' => null, 'id_pasien_pmo' => null,
            'user_pasien_id' => $p->id, 'user_pmo_id' => null,
            'waktu_jadwal' => '2026-06-24 08:00:00', 'status' => 'terlewat']);

        $this->assertSame(2, DashboardRepository::hitungStreak($p->id));
    }

    public function test_untuk_pasien_mengembalikan_struktur_lengkap(): void
    {
        $vm = \App\Services\DashboardService::untukPasien($this->pasien());

        $this->assertArrayHasKey('obat_hari_ini', $vm);
        $this->assertArrayHasKey('kepatuhan', $vm);
        $this->assertArrayHasKey('streak', $vm);
        $this->assertArrayHasKey('jadwal_hari_ini', $vm);
        $this->assertArrayHasKey('gd_trend', $vm);
        $this->assertSame(0, $vm['kepatuhan']);
        $this->assertSame([], $vm['jadwal_hari_ini']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=DashboardServiceTest`
Expected: FAIL dengan "Class App\Repos\DashboardRepository not found".

- [ ] **Step 3: Implement DashboardRepository**

Create `app/Repos/DashboardRepository.php`:

```php
<?php

namespace App\Repos;

use App\Models\JadwalCgdPeserta;
use App\Models\PengingatCgdLog;
use App\Models\PengingatKejadian;
use App\Models\PengingatMoLog;
use Illuminate\Support\Carbon;

class DashboardRepository
{
    public static function hitungKepatuhanMo(string $pasienId, int $hari = 30): int
    {
        $sejak = Carbon::today()->subDays($hari - 1)->toDateString();

        $total = PengingatMoLog::query()->forUser($pasienId)
            ->where('tgl_minum_obat', '>=', $sejak)->count();

        if ($total === 0) {
            return 0;
        }

        $tepat = PengingatMoLog::query()->forUser($pasienId)
            ->where('tgl_minum_obat', '>=', $sejak)
            ->whereRaw('ABS(patuh_menit) <= 15')->count();

        return (int) round($tepat / $total * 100);
    }

    public static function hitungStreak(string $pasienId): int
    {
        // Ambil semua tanggal yang punya kejadian "terlewat", lalu hitung
        // berapa hari berturut-turut dari hari ini yang TIDAK punya terlewat.
        $terlewat = PengingatKejadian::query()
            ->where('user_pasien_id', $pasienId)
            ->where('status', PengingatKejadian::STATUS_TERLEWAT)
            ->pluck('waktu_jadwal')
            ->map(fn ($w) => Carbon::parse($w)->toDateString())
            ->unique()->flip();

        $adaRiwayat = PengingatKejadian::query()
            ->where('user_pasien_id', $pasienId)->exists();

        if (! $adaRiwayat) {
            return 0;
        }

        $streak = 0;
        $tanggal = Carbon::today();
        while (! $terlewat->has($tanggal->toDateString()) && $streak < 366) {
            $streak++;
            $tanggal = $tanggal->subDay();
        }

        return $streak;
    }

    public static function kejadianMoHariIni(string $pasienId): array
    {
        $hariIni = Carbon::today();
        $base = PengingatKejadian::query()
            ->where('user_pasien_id', $pasienId)
            ->where('jenis', 'mo')
            ->whereDate('waktu_jadwal', $hariIni);

        return [
            'total' => (clone $base)->count(),
            'selesai' => (clone $base)->where('status', PengingatKejadian::STATUS_DIKONFIRMASI)->count(),
        ];
    }

    public static function cgdHariIni(string $pasienId): array
    {
        $today = Carbon::today()->toDateString();

        $total = JadwalCgdPeserta::query()
            ->whereHas('jadwalCgd', fn ($q) => $q->whereDate('tgl_jadwal_cgd', $today))
            ->whereHas('pasienPmo', fn ($q) => $q->where('id_user', $pasienId))
            ->count();

        $selesai = PengingatCgdLog::query()->forUser($pasienId)
            ->whereDate('tgl_cgd', $today)->count();

        return ['total' => $total, 'selesai' => $selesai];
    }

    public static function trenGdPasien(string $pasienId, int $limit = 14): array
    {
        return PengingatCgdLog::query()->forUser($pasienId)
            ->orderByDesc('tgl_cgd')->orderByDesc('jam_cgd')
            ->limit($limit)->get(['tgl_cgd', 'hasil_mgdl'])
            ->reverse()->values()
            ->map(fn ($r) => ['tgl' => $r->tgl_cgd->format('Y-m-d'), 'hasil' => (int) $r->hasil_mgdl])
            ->all();
    }
}
```

> Catatan: jika relasi `JadwalCgdPeserta::pasienPmo` / `jadwalCgd` belum bernama persis demikian, sesuaikan dengan nama relasi yang ada di model (cek `app/Models/JadwalCgdPeserta.php`). Bila `cgdHariIni` sulit, sementara boleh kembalikan `['total' => 0, 'selesai' => $selesai]` agar test struktur tetap lulus — tapi utamakan query benar.

- [ ] **Step 4: Implement DashboardService::untukPasien**

Create `app/Services/DashboardService.php`:

```php
<?php

namespace App\Services;

use App\Models\Edukasi;
use App\Models\PasienPmo;
use App\Models\PengingatCgdLog;
use App\Models\PengingatKejadian;
use App\Models\Pengumuman;
use App\Models\User;
use App\Repos\DashboardRepository;
use Illuminate\Support\Carbon;

class DashboardService
{
    public static function untukPasien(User $pasien): array
    {
        $mo = DashboardRepository::kejadianMoHariIni($pasien->id);
        $cgd = DashboardRepository::cgdHariIni($pasien->id);

        return [
            'obat_hari_ini' => $mo['total'],
            'obat_selesai' => $mo['selesai'],
            'cgd_hari_ini' => $cgd['total'],
            'cgd_selesai' => $cgd['selesai'],
            'kepatuhan' => DashboardRepository::hitungKepatuhanMo($pasien->id),
            'streak' => DashboardRepository::hitungStreak($pasien->id),
            'jadwal_hari_ini' => self::jadwalHariIniPasien($pasien->id),
            'gd_trend' => DashboardRepository::trenGdPasien($pasien->id),
            'pmo' => self::infoPmo($pasien->id),
            'pengumuman' => self::pengumumanTerbaru(),
            'tips' => self::tips(),
        ];
    }

    private static function jadwalHariIniPasien(string $pasienId): array
    {
        return PengingatKejadian::query()
            ->where('user_pasien_id', $pasienId)
            ->whereDate('waktu_jadwal', Carbon::today())
            ->orderBy('waktu_jadwal')
            ->get()
            ->map(fn ($k) => [
                'waktu' => Carbon::parse($k->waktu_jadwal)->format('H:i'),
                'jenis' => $k->jenis,
                'status' => match ($k->status) {
                    PengingatKejadian::STATUS_DIKONFIRMASI => 'done',
                    PengingatKejadian::STATUS_TERLEWAT => 'missed',
                    default => 'upcoming',
                },
            ])->all();
    }

    private static function infoPmo(string $pasienId): ?array
    {
        $pp = PasienPmo::query()->forPasien($pasienId)->active()->with('pmo.biodata')->first();
        if (! $pp) {
            return null;
        }

        return [
            'nama' => $pp->nama_pmo,
            'jenis' => $pp->jenis_pmo,
            'whatsapp' => $pp->pmo?->biodata?->no_hp ?? $pp->pmo?->biodata?->whatsapp ?? null,
        ];
    }

    private static function pengumumanTerbaru(int $limit = 3): array
    {
        return Pengumuman::query()->latest()->limit($limit)->get()
            ->map(fn ($p) => ['title' => $p->judul, 'meta' => optional($p->created_at)->translatedFormat('d M Y')])
            ->all();
    }

    private static function tips(): array
    {
        $edukasi = Edukasi::query()->latest()->limit(4)->get();
        if ($edukasi->isNotEmpty()) {
            return $edukasi->map(fn ($e) => ['icon' => '📖', 'text' => $e->judul])->all();
        }

        return [
            ['icon' => '💧', 'text' => 'Minum air putih minimal 8 gelas per hari.'],
            ['icon' => '🥗', 'text' => 'Pilih karbohidrat kompleks: nasi merah, oat, atau ubi.'],
            ['icon' => '🚶', 'text' => 'Jalan kaki 30 menit setelah makan menurunkan gula darah.'],
        ];
    }
}
```

> Sesuaikan nama field konten bila berbeda: cek `app/Models/Pengumuman.php` (`judul`) & `app/Models/Edukasi.php` (`judul`), dan field no HP di `app/Models/UserBiodata.php`. Bila scope `latest()` butuh kolom `published`/`status`, tambahkan filter terbit.

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter=DashboardServiceTest`
Expected: PASS (4 tests).

- [ ] **Step 6: Format & commit**

```bash
vendor/bin/pint app/Services/DashboardService.php app/Repos/DashboardRepository.php
git add app/Services/DashboardService.php app/Repos/DashboardRepository.php tests/Unit/Dashboard/DashboardServiceTest.php
git commit -m "feat(dashboard): service & repo metrik pasien (data nyata)"
```

---

## Task 2: Controller + route + blade Pasien

**Files:**
- Create: `app/Http/Controllers/DashboardController.php`
- Modify: `routes/web.php:241`
- Modify: `resources/views/dashboard/pasien.blade.php` (blok `@php` ~476–571 + chart JS ~871)
- Test: `tests/Feature/Dashboard/DashboardPasienTest.php`

**Interfaces:**
- Consumes: `DashboardService::untukPasien()` (Task 1).
- Produces: `DashboardController::pasien(): \Illuminate\View\View`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Dashboard/DashboardPasienTest.php`:

```php
<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPasienTest extends TestCase
{
    use RefreshDatabase;

    public function test_pasien_dapat_membuka_dashboard(): void
    {
        $pasien = User::factory()->create(['role' => 'pasien', 'is_active' => true]);

        $this->actingAs($pasien)->get('/pasien/dashboard')
            ->assertOk()
            ->assertViewHas('kepatuhan', 0);
    }

    public function test_non_pasien_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->get('/pasien/dashboard')->assertForbidden();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=DashboardPasienTest`
Expected: FAIL (route masih `Route::view`, tidak ada `kepatuhan`).

- [ ] **Step 3: Create DashboardController**

Create `app/Http/Controllers/DashboardController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function pasien(Request $request): View
    {
        return view('dashboard.pasien', DashboardService::untukPasien($request->user()));
    }

    public function pmo(Request $request): View
    {
        return view('dashboard.pmo', DashboardService::untukPmo($request->user()));
    }

    public function admin(Request $request): View
    {
        return view('dashboard.admin', DashboardService::untukAdmin('admin'));
    }

    public function superadmin(Request $request): View
    {
        return view('dashboard.superadmin', DashboardService::untukAdmin('superadmin'));
    }
}
```

> Jika `Controller` base tidak ada, extend `App\Http\Controllers\BaseController` (cek controller lain).

- [ ] **Step 4: Wire pasien route**

Di `routes/web.php`, tambahkan import di atas (dekat use lain):

```php
use App\Http\Controllers\DashboardController;
```

Ganti baris 241:

```php
Route::get('/dashboard', [DashboardController::class, 'pasien'])->name('dashboard');
```

- [ ] **Step 5: Replace dummy block in blade**

Di `resources/views/dashboard/pasien.blade.php`, **hapus** seluruh blok `@php ... @endphp` dummy (≈ baris 476–571) dan ganti dengan blok yang membaca variabel dari controller, **mempertahankan nama variabel lokal yang sudah dipakai markup di bawahnya**:

```blade
@php
    // Data nyata dari DashboardController@pasien
    $obatHariIni = $obat_hari_ini;
    $obatSelesai = $obat_selesai;
    $cgdHariIni  = $cgd_hari_ini;
    $cgdSelesai  = $cgd_selesai;
    $kepatuhan   = $kepatuhan;
    $streak      = $streak;

    $pmoName     = $pmo['nama'] ?? null;
    $pmoHubungan = $pmo['jenis'] ?? '-';
    $pmoWhatsapp = $pmo['whatsapp'] ?? null;

    $jadwalHariIni = $jadwal_hari_ini;   // [] bila kosong
    $pengumuman    = $pengumuman;         // [] bila kosong
    $tips          = $tips;
@endphp
```

Lalu pada bagian yang me-loop `$jadwalHariIni` dan `$pengumuman`, ubah `@foreach` menjadi `@forelse ... @empty` dengan empty-state, contoh:

```blade
@forelse ($jadwalHariIni as $item)
    {{-- markup baris jadwal yang sudah ada --}}
@empty
    <div class="text-muted text-center py-4">Belum ada jadwal untuk hari ini.</div>
@endforelse
```

Untuk chart tren GD (JS ≈ baris 871), ganti array hardcoded `gdLabels`/`gdData` dengan data server:

```blade
const gdTrend = @json($gd_trend);
const gdLabels = gdTrend.map(d => d.tgl);
const gdData   = gdTrend.map(d => d.hasil);
const gdEmpty  = gdTrend.length === 0;
```

Bila `gdEmpty`, sembunyikan canvas dan tampilkan teks "Belum ada data gula darah." (tambahkan `<div>` empty-state dengan `@if(empty($gd_trend))`).

> Variabel `$weekTracker`/`$weekSummary` (tracker mingguan) belum dihitung di Task 1 — untuk putaran ini set empty-state: ganti blok yang memakainya dengan `@if(false)` sementara, ATAU hapus section tracker. Catat di commit message bahwa tracker mingguan menyusul.

- [ ] **Step 6: Run tests + manual check**

Run: `php artisan test --filter=DashboardPasienTest`
Expected: PASS (2 tests). Pastikan tidak ada `Undefined variable` saat render.

- [ ] **Step 7: Format & commit**

```bash
vendor/bin/pint app/Http/Controllers/DashboardController.php
git add app/Http/Controllers/DashboardController.php routes/web.php resources/views/dashboard/pasien.blade.php tests/Feature/Dashboard/DashboardPasienTest.php
git commit -m "feat(dashboard): pasien pakai data nyata + empty-state"
```

---

## Task 3: DashboardService — metrik PMO

**Files:**
- Modify: `app/Services/DashboardService.php`
- Modify: `app/Repos/DashboardRepository.php`
- Test: `tests/Unit/Dashboard/DashboardServiceTest.php` (tambah method)

**Interfaces:**
- Produces:
  - `DashboardRepository::pasienBinaan(string $pmoId): \Illuminate\Support\Collection` (PasienPmo aktif)
  - `DashboardService::untukPmo(User $pmo): array` dengan kunci `total_pasien`, `patuh_hari_ini`, `perlu_perhatian`, `total_jadwal_hari_ini`, `daftar_pasien`, `timeline`, `tips`.

- [ ] **Step 1: Write the failing test**

Tambahkan ke `DashboardServiceTest`:

```php
public function test_untuk_pmo_menghitung_total_pasien_binaan(): void
{
    $pmo = User::factory()->create(['role' => 'pmo', 'is_active' => true]);
    $p1 = User::factory()->create(['role' => 'pasien', 'is_active' => true]);
    $p2 = User::factory()->create(['role' => 'pasien', 'is_active' => true]);

    foreach ([$p1, $p2] as $p) {
        \App\Models\PasienPmo::create([
            'id_user' => $p->id, 'pmo_user_id' => $pmo->id,
            'nama_pasien' => $p->name, 'nik' => fake()->numerify('################'),
            'nama_pmo' => $pmo->name, 'jenis_pmo' => 'Keluarga',
            'tanggal_regis' => now()->toDateString(), 'status_diabetes' => 'Tipe 2',
            'is_active' => true,
        ]);
    }

    $vm = \App\Services\DashboardService::untukPmo($pmo);

    $this->assertSame(2, $vm['total_pasien']);
    $this->assertArrayHasKey('daftar_pasien', $vm);
    $this->assertArrayHasKey('timeline', $vm);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_untuk_pmo_menghitung_total_pasien_binaan`
Expected: FAIL "Call to undefined method ...untukPmo()".

- [ ] **Step 3: Implement repo helper**

Tambahkan ke `DashboardRepository`:

```php
public static function pasienBinaan(string $pmoId): \Illuminate\Support\Collection
{
    return \App\Models\PasienPmo::query()->forPmo($pmoId)->active()->get();
}

public static function hasilGdTerakhir(string $pasienId): ?int
{
    $log = PengingatCgdLog::query()->forUser($pasienId)
        ->orderByDesc('tgl_cgd')->orderByDesc('jam_cgd')->first();

    return $log?->hasil_mgdl;
}
```

- [ ] **Step 4: Implement untukPmo**

Tambahkan ke `DashboardService`:

```php
public static function untukPmo(User $pmo): array
{
    $binaan = DashboardRepository::pasienBinaan($pmo->id);
    $pasienIds = $binaan->pluck('id_user')->all();

    $patuhHariIni = PengingatKejadian::query()
        ->whereIn('user_pasien_id', $pasienIds)
        ->whereDate('waktu_jadwal', Carbon::today())
        ->where('status', PengingatKejadian::STATUS_DIKONFIRMASI)->count();

    $totalJadwal = PengingatKejadian::query()
        ->whereIn('user_pasien_id', $pasienIds)
        ->whereDate('waktu_jadwal', Carbon::today())->count();

    $perluPerhatian = empty($pasienIds) ? 0 : PengingatKejadian::query()
        ->whereIn('user_pasien_id', $pasienIds)
        ->whereDate('waktu_jadwal', Carbon::today())
        ->where('status', PengingatKejadian::STATUS_TERLEWAT)
        ->distinct('user_pasien_id')->count('user_pasien_id');

    $daftar = $binaan->map(fn ($pp) => [
        'nama' => $pp->nama_pasien,
        'status_diabetes' => $pp->status_diabetes,
        'kepatuhan' => DashboardRepository::hitungKepatuhanMo($pp->id_user),
        'gd_terakhir' => DashboardRepository::hasilGdTerakhir($pp->id_user),
    ])->all();

    return [
        'total_pasien' => $binaan->count(),
        'patuh_hari_ini' => $patuhHariIni,
        'perlu_perhatian' => $perluPerhatian,
        'total_jadwal_hari_ini' => $totalJadwal,
        'daftar_pasien' => $daftar,
        'timeline' => self::timelinePmo($pasienIds),
        'tips' => self::tips(),
    ];
}

private static function timelinePmo(array $pasienIds, int $limit = 10): array
{
    if (empty($pasienIds)) {
        return [];
    }

    return \App\Models\PengingatMoLog::query()
        ->whereIn('id_user', $pasienIds)
        ->orderByDesc('tgl_minum_obat')->orderByDesc('jam_minum_obat')
        ->limit($limit)->get()
        ->map(fn ($l) => [
            'nama' => $l->nama_pasien,
            'aksi' => 'Minum '.$l->nama_obat,
            'waktu' => $l->jam_minum_obat_format,
            'tgl' => optional($l->tgl_minum_obat)->format('d M'),
        ])->all();
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter=DashboardServiceTest`
Expected: PASS (semua, termasuk test PMO baru).

- [ ] **Step 6: Format & commit**

```bash
vendor/bin/pint app/Services/DashboardService.php app/Repos/DashboardRepository.php
git add app/Services/DashboardService.php app/Repos/DashboardRepository.php tests/Unit/Dashboard/DashboardServiceTest.php
git commit -m "feat(dashboard): service metrik PMO (pasien binaan, kepatuhan, timeline)"
```

---

## Task 4: Controller route + blade PMO

**Files:**
- Modify: `routes/web.php:261`
- Modify: `resources/views/dashboard/pmo.blade.php` (blok `@php` ~498–633)
- Test: `tests/Feature/Dashboard/DashboardPmoTest.php`

**Interfaces:**
- Consumes: `DashboardService::untukPmo()` (Task 3), `DashboardController::pmo()` (Task 2).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Dashboard/DashboardPmoTest.php`:

```php
<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPmoTest extends TestCase
{
    use RefreshDatabase;

    public function test_pmo_dapat_membuka_dashboard(): void
    {
        $pmo = User::factory()->create(['role' => 'pmo', 'is_active' => true]);

        $this->actingAs($pmo)->get('/pmo/dashboard')
            ->assertOk()
            ->assertViewHas('total_pasien', 0);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=DashboardPmoTest`
Expected: FAIL (route masih `Route::view`).

- [ ] **Step 3: Wire pmo route**

Ganti `routes/web.php:261`:

```php
Route::get('/dashboard', [DashboardController::class, 'pmo'])->name('dashboard');
```

- [ ] **Step 4: Replace dummy block in pmo blade**

Di `resources/views/dashboard/pmo.blade.php`, buka blok `@php` dummy (≈498–633), catat nama variabel lokal yang dipakai markup di bawah (mis. `$totalPasien`, `$patuhHariIni`, `$perluPerhatian`, `$totalJadwalHariIni`, daftar pasien, timeline). Ganti assignment hardcoded dengan pembacaan dari variabel controller, pertahankan nama lokal:

```blade
@php
    $totalPasien        = $total_pasien;
    $patuhHariIni       = $patuh_hari_ini;
    $perluPerhatian     = $perlu_perhatian;
    $totalJadwalHariIni = $total_jadwal_hari_ini;
    // hapus $streakPendampingan (tidak dihitung di putaran ini) atau set 0
    $daftarPasien       = $daftar_pasien;   // [] bila kosong
    $timeline           = $timeline;         // [] bila kosong
@endphp
```

Ubah loop daftar pasien & timeline jadi `@forelse ... @empty` dengan empty-state ("Belum ada pasien binaan." / "Belum ada aktivitas."). Sesuaikan key array markup (`['nama']`, `['kepatuhan']`, `['gd_terakhir']`, `['status_diabetes']`) dengan struktur dari Task 3.

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=DashboardPmoTest`
Expected: PASS. Render tanpa `Undefined variable`.

- [ ] **Step 6: Format & commit**

```bash
git add routes/web.php resources/views/dashboard/pmo.blade.php tests/Feature/Dashboard/DashboardPmoTest.php
git commit -m "feat(dashboard): PMO pakai data nyata + empty-state"
```

---

## Task 5: DashboardService — metrik Admin/Superadmin

**Files:**
- Modify: `app/Services/DashboardService.php`
- Modify: `app/Repos/DashboardRepository.php`
- Test: `tests/Unit/Dashboard/DashboardServiceTest.php`

**Interfaces:**
- Produces: `DashboardService::untukAdmin(string $role): array` dengan kunci `total_pasien`, `total_pmo`, `total_obat`, `perlu_tindak_lanjut`, `tren_30hari`, `distribusi_kategori`, `aktivitas_terbaru`, plus (superadmin) `ringkasan_user`.

- [ ] **Step 1: Write the failing test**

Tambahkan ke `DashboardServiceTest`:

```php
public function test_untuk_admin_menghitung_total_master(): void
{
    \App\Models\MasterObat::factory()->count(3)->create();
    User::factory()->create(['role' => 'pmo', 'is_active' => true]);

    $vm = \App\Services\DashboardService::untukAdmin('admin');

    $this->assertSame(3, $vm['total_obat']);
    $this->assertSame(1, $vm['total_pmo']);
    $this->assertArrayHasKey('tren_30hari', $vm);
    $this->assertArrayHasKey('distribusi_kategori', $vm);
    $this->assertArrayNotHasKey('ringkasan_user', $vm);
}

public function test_superadmin_punya_ringkasan_user(): void
{
    $vm = \App\Services\DashboardService::untukAdmin('superadmin');
    $this->assertArrayHasKey('ringkasan_user', $vm);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_untuk_admin_menghitung_total_master`
Expected: FAIL "undefined method untukAdmin()".

- [ ] **Step 3: Implement repo helpers**

Tambahkan ke `DashboardRepository`:

```php
public static function distribusiKategoriCgd(): array
{
    $rows = PengingatCgdLog::query()
        ->selectRaw('kategori_hasil, COUNT(*) as jml')
        ->groupBy('kategori_hasil')->pluck('jml', 'kategori_hasil');

    return [
        'normal' => (int) ($rows['normal'] ?? 0),
        'tidak_terkontrol' => (int) ($rows['tidak_terkontrol'] ?? 0),
        'tinggi' => (int) ($rows['tinggi'] ?? 0),
        'berbahaya' => (int) ($rows['berbahaya'] ?? 0),
    ];
}

public static function trenCgd30Hari(): array
{
    $sejak = Carbon::today()->subDays(29)->toDateString();
    $rows = PengingatCgdLog::query()
        ->where('tgl_cgd', '>=', $sejak)
        ->selectRaw('tgl_cgd, COUNT(*) as jml')
        ->groupBy('tgl_cgd')->pluck('jml', 'tgl_cgd');

    $hasil = [];
    for ($i = 29; $i >= 0; $i--) {
        $t = Carbon::today()->subDays($i)->toDateString();
        $hasil[] = ['tgl' => $t, 'jml' => (int) ($rows[$t] ?? 0)];
    }

    return $hasil;
}

public static function tindakLanjutHariIni(): int
{
    return PengingatKejadian::query()
        ->whereDate('waktu_jadwal', Carbon::today())
        ->where('status', PengingatKejadian::STATUS_TERLEWAT)->count();
}
```

- [ ] **Step 4: Implement untukAdmin**

Tambahkan ke `DashboardService` (import `App\Models\MasterObat`):

```php
public static function untukAdmin(string $role): array
{
    $data = [
        'total_pasien' => User::query()->where('role', 'pasien')->count(),
        'total_pmo' => User::query()->where('role', 'pmo')->count(),
        'total_obat' => \App\Models\MasterObat::query()->count(),
        'perlu_tindak_lanjut' => DashboardRepository::tindakLanjutHariIni(),
        'tren_30hari' => DashboardRepository::trenCgd30Hari(),
        'distribusi_kategori' => DashboardRepository::distribusiKategoriCgd(),
        'aktivitas_terbaru' => self::timelinePmo(
            \App\Models\PasienPmo::query()->pluck('id_user')->all()
        ),
    ];

    if ($role === 'superadmin') {
        $data['ringkasan_user'] = User::query()
            ->selectRaw('role, COUNT(*) as jml')->groupBy('role')
            ->pluck('jml', 'role')->all();
    }

    return $data;
}
```

> `timelinePmo` sudah ada dari Task 3 (private). Karena dipanggil dari `untukAdmin`, pastikan tetap `private static` di kelas yang sama — tidak perlu ubah visibilitas.

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter=DashboardServiceTest`
Expected: PASS semua.

- [ ] **Step 6: Format & commit**

```bash
vendor/bin/pint app/Services/DashboardService.php app/Repos/DashboardRepository.php
git add app/Services/DashboardService.php app/Repos/DashboardRepository.php tests/Unit/Dashboard/DashboardServiceTest.php
git commit -m "feat(dashboard): service metrik admin & superadmin"
```

---

## Task 6: Routes + blade Admin & Superadmin

**Files:**
- Modify: `routes/web.php:596` (superadmin), `routes/web.php:607` (admin)
- Modify: `resources/views/dashboard/admin.blade.php`, `resources/views/dashboard/superadmin.blade.php`
- Test: `tests/Feature/Dashboard/DashboardAdminTest.php`

**Interfaces:**
- Consumes: `DashboardController::admin()` & `superadmin()` (Task 2), `DashboardService::untukAdmin()` (Task 5).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Dashboard/DashboardAdminTest.php`:

```php
<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_membuka_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertViewHas('total_obat', 0);
    }

    public function test_superadmin_dapat_membuka_dashboard(): void
    {
        $su = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);

        $this->actingAs($su)->get('/superadmin/dashboard')
            ->assertOk()
            ->assertViewHas('ringkasan_user');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=DashboardAdminTest`
Expected: FAIL (route masih `Route::view`).

- [ ] **Step 3: Wire routes**

Ganti `routes/web.php:596`:

```php
Route::get('/dashboard', [DashboardController::class, 'superadmin'])->name('dashboard');
```

Ganti `routes/web.php:607`:

```php
Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
```

- [ ] **Step 4: Replace dummy blocks in admin & superadmin blades**

Untuk `resources/views/dashboard/superadmin.blade.php` — ganti blok `@php $stats = [...]` (≈167–172) agar memakai data controller:

```blade
@php
    $stats = [
        'pasien'  => ['value' => $total_pasien, 'delta' => 0, 'trend' => 'up'],
        'pmo'     => ['value' => $total_pmo, 'delta' => 0, 'trend' => 'up'],
        'obat'    => ['value' => $total_obat, 'delta' => 0, 'trend' => 'up'],
        'pending' => ['value' => $perlu_tindak_lanjut, 'delta' => 0, 'trend' => 'down'],
    ];
@endphp
```

Ganti chart JS hardcoded (≈365–374) dengan data server:

```blade
const trendRows = @json($tren_30hari);
const trendData = trendRows.map(r => r.jml);
const trendLabels = trendRows.map(r => r.tgl);

const distribusi = @json($distribusi_kategori);
const distData = [distribusi.normal, distribusi.tidak_terkontrol, distribusi.tinggi, distribusi.berbahaya];
```

Bila semua nol, tampilkan empty-state teks di atas canvas (`@if(array_sum($distribusi_kategori) === 0) ... @endif`).

Untuk `resources/views/dashboard/admin.blade.php` — buka file, temukan blok `@php` dummy-nya, dan terapkan pemetaan yang sama (`$total_pasien`, `$total_pmo`, `$total_obat`, `$perlu_tindak_lanjut`, `$tren_30hari`, `$distribusi_kategori`, `$aktivitas_terbaru`), pertahankan nama variabel lokal yang sudah ada di markup, dan ubah loop ke `@forelse ... @empty`.

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=DashboardAdminTest`
Expected: PASS (2 tests). Render kedua blade tanpa `Undefined variable`.

- [ ] **Step 6: Run full suite + format**

Run: `composer test`
Expected: tidak ada regresi baru (2 test auth Breeze yang memang merah sejak awal boleh tetap merah — lihat memory auth-tests-pre-existing-fail).

```bash
vendor/bin/pint
git add routes/web.php resources/views/dashboard/admin.blade.php resources/views/dashboard/superadmin.blade.php tests/Feature/Dashboard/DashboardAdminTest.php
git commit -m "feat(dashboard): admin & superadmin pakai data nyata + empty-state"
```

---

## Self-Review (sudah dijalankan penulis plan)

**Spec coverage:** Pasien (Task 1–2), PMO (Task 3–4), Admin & Superadmin (Task 5–6), empty-state (tiap blade task), definisi kepatuhan & streak (Task 1), Pengumuman/Edukasi (Task 1), pengujian (tiap task) — semua tercakup. Tracker mingguan pasien & "delta" kartu superadmin secara sadar di-defer ke empty-state/0 (dicatat di Task 2 & 6) karena di luar lingkup "esensial dulu".

**Placeholder scan:** Tidak ada TODO/TBD pada langkah kode. Catatan "sesuaikan nama relasi/field" adalah instruksi verifikasi konkret terhadap file model yang disebut, bukan placeholder kerja.

**Type consistency:** Kunci array view-model konsisten antar task (`total_pasien`, `kepatuhan`, dst). `timelinePmo()` didefinisikan di Task 3 dan dipakai ulang di Task 5 dengan signature sama. Nama method repo/service konsisten dengan blok Interfaces.

## Catatan ketergantungan yang harus diverifikasi saat eksekusi

Sebelum/awal Task 1, cek cepat nama relasi & field aktual (hindari asumsi):
- `app/Models/JadwalCgdPeserta.php` → nama relasi ke `JadwalCgd` & ke `PasienPmo`, dan kolom tanggal `tgl_jadwal_cgd` di `JadwalCgd`.
- `app/Models/Pengumuman.php` & `Edukasi.php` → kolom judul + apakah ada kolom status terbit.
- `app/Models/UserBiodata.php` → nama kolom no HP/WhatsApp.
- Kolom `users.role` & `users.is_active` ada (dipakai test).
