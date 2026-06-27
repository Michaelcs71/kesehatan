# Master Pasien & PMO Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mengganti 2 placeholder admin (`/admin/master/pasien`, `/admin/master/pmo`) dengan direktori + halaman detail read-focused, memakai ulang `DashboardRepository` & `PasienRiwayatService`, tanpa CRUD baru.

**Architecture:** `MasterPasienController` & `MasterPmoController` (tipis) memanggil `MasterDirektoriService` (statis) yang meng-query roster pasien (`PasienPmo`) & PMO (`User` role pmo) dan meng-agregasi kepatuhan/jadwal/riwayat via service yang sudah ada. View server-rendered + pagination + cari.

**Tech Stack:** Laravel 12, PHP 8.2, Eloquent, Blade (layouts.app, Bootstrap 5/CoreUI/RemixIcon), PHPUnit (sqlite :memory:).

## Global Constraints

- Bahasa domain **Indonesian** untuk nama method, variabel, dan string UI.
- Service memakai **method statis** (ikuti konvensi codebase).
- Controller tipis: panggil service, `return view(...)` / `abort(404)`.
- UUID primary keys.
- Read-only: TIDAK ada create/edit/delete di modul ini.
- Roster pasien = `PasienPmo` (scope `active`, `search`); roster PMO = `User::where('role','pmo')`.
- Kepatuhan via `DashboardRepository::hitungKepatuhanMo($idUser)`; binaan via `DashboardRepository::pasienBinaan($pmoId)` & `hasilGdTerakhir($idUser)`; jadwal CGD mendatang via `PasienRiwayatService::jadwalCgdPasien($idUser)['mendatang']`.
- Kontak diambil dari `users.whatsapp_number`; biodata dari `User->biodata` (`UserBiodata`: `jenis_kelamin_label`, `tanggal_lahir`, `alamat_lengkap`).
- Route di grup admin yang sudah ada (`['auth','verified','role:admin,superadmin']`, `prefix('admin')`, `name('admin.')`), tiap aksi `->middleware('permission:...')`. Nama route: `admin.master.pasien`, `admin.master.pasien.show`, `admin.master.pmo`, `admin.master.pmo.show`. Param `{id}` dibatasi `->where('id','[0-9a-f\-]+')`.
- Tests memakai `RefreshDatabase`. **Untuk lolos `permission:` middleware tanpa seeding, user admin di test dibuat superadmin** (`User` meng-override `hasPermissionTo`: superadmin selalu lolos), role `superadmin` juga lolos `role:admin,superadmin`. Kasus "ditolak" memakai user role `pasien` (→ 403 dari `role:` middleware).

---

## File Structure

- Create: `app/Services/MasterDirektoriService.php` — query roster + detail (statis).
- Create: `app/Http/Controllers/MasterPasienController.php` — `index`, `show`.
- Create: `app/Http/Controllers/MasterPmoController.php` — `index`, `show`.
- Create: `resources/views/master-pasien/index.blade.php`, `resources/views/master-pasien/show.blade.php`, `resources/views/master-pmo/index.blade.php`, `resources/views/master-pmo/show.blade.php`.
- Modify: `routes/web.php` (~607–610) — ganti 2 `Route::view` + tambah route `show`; tambah import.
- Modify: `resources/views/components/sidebar.blade.php` (~111–129) — tambah 2 item di grup Master Data.
- Create tests: `tests/Feature/MasterDirektori/MasterDirektoriServiceTest.php`, `MasterPasienTest.php`, `MasterPmoTest.php`.

---

## Task 1: MasterDirektoriService — bagian Pasien

**Files:**
- Create: `app/Services/MasterDirektoriService.php`
- Test: `tests/Feature/MasterDirektori/MasterDirektoriServiceTest.php`

**Interfaces:**
- Produces:
  - `MasterDirektoriService::daftarPasien(array $filter = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator` — tiap item array `['id_user','nama','nik','status_diabetes','nama_pmo','kepatuhan','is_active']`.
  - `MasterDirektoriService::detailPasien(string $userId): ?array` — `null` bila bukan pasien (tak punya `PasienPmo`).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/MasterDirektori/MasterDirektoriServiceTest.php`:

```php
<?php

namespace Tests\Feature\MasterDirektori;

use App\Models\PasienPmo;
use App\Models\User;
use App\Services\MasterDirektoriService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDirektoriServiceTest extends TestCase
{
    use RefreshDatabase;

    private function pasienDenganPmo(string $nama, ?string $nik = null): User
    {
        $p = User::factory()->create(['role' => 'pasien', 'is_active' => true]);
        PasienPmo::create([
            'id_user' => $p->id, 'pmo_user_id' => null, 'nama_pasien' => $nama,
            'nik' => $nik ?? fake()->numerify('################'), 'nama_pmo' => '-',
            'jenis_pmo' => 'Keluarga', 'tanggal_regis' => now()->toDateString(),
            'status_diabetes' => 'Tipe 2', 'is_active' => true,
        ]);

        return $p;
    }

    public function test_daftar_pasien_memetakan_kolom_dan_kepatuhan(): void
    {
        $this->pasienDenganPmo('Siti Aminah');

        $page = MasterDirektoriService::daftarPasien();

        $this->assertSame(1, $page->total());
        $row = $page->items()[0];
        $this->assertSame('Siti Aminah', $row['nama']);
        $this->assertArrayHasKey('kepatuhan', $row);
        $this->assertSame(0, $row['kepatuhan']); // belum ada log
    }

    public function test_daftar_pasien_menghormati_cari(): void
    {
        $this->pasienDenganPmo('Siti Aminah');
        $this->pasienDenganPmo('Budi Santoso');

        $page = MasterDirektoriService::daftarPasien(['cari' => 'Budi']);

        $this->assertSame(1, $page->total());
        $this->assertSame('Budi Santoso', $page->items()[0]['nama']);
    }

    public function test_detail_pasien_null_untuk_non_pasien(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->assertNull(MasterDirektoriService::detailPasien($admin->id));
    }

    public function test_detail_pasien_mengembalikan_struktur(): void
    {
        $p = $this->pasienDenganPmo('Siti Aminah');

        $d = MasterDirektoriService::detailPasien($p->id);

        $this->assertSame('Siti Aminah', $d['nama']);
        $this->assertArrayHasKey('kepatuhan', $d);
        $this->assertArrayHasKey('jadwal_mo', $d);
        $this->assertArrayHasKey('jadwal_cgd', $d);
        $this->assertArrayHasKey('riwayat_mo', $d);
        $this->assertArrayHasKey('riwayat_cgd', $d);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=MasterDirektoriServiceTest`
Expected: FAIL "Class App\Services\MasterDirektoriService not found".

- [ ] **Step 3: Implement the service (bagian pasien)**

Create `app/Services/MasterDirektoriService.php`:

```php
<?php

namespace App\Services;

use App\Models\JadwalMinumObat;
use App\Models\PasienPmo;
use App\Models\PengingatCgdLog;
use App\Models\PengingatMoLog;
use App\Repos\DashboardRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MasterDirektoriService
{
    public static function daftarPasien(array $filter = []): LengthAwarePaginator
    {
        return PasienPmo::query()->active()
            ->when($filter['cari'] ?? null, fn ($q, $c) => $q->search($c))
            ->with(['pasien', 'pmo'])
            ->orderBy('nama_pasien')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($pp) => [
                'id_user' => $pp->id_user,
                'nama' => $pp->nama_pasien,
                'nik' => $pp->nik,
                'status_diabetes' => $pp->status_diabetes,
                'nama_pmo' => $pp->nama_pmo,
                'kepatuhan' => DashboardRepository::hitungKepatuhanMo($pp->id_user),
                'is_active' => $pp->is_active,
            ]);
    }

    public static function detailPasien(string $userId): ?array
    {
        $pp = PasienPmo::query()->where('id_user', $userId)
            ->with(['pasien.biodata', 'pmo'])->first();

        if (! $pp) {
            return null;
        }

        $pasien = $pp->pasien;

        return [
            'nama' => $pp->nama_pasien,
            'nik' => $pp->nik,
            'jenis_kelamin' => $pasien?->biodata?->jenis_kelamin_label ?? '-',
            'tanggal_lahir' => $pasien?->biodata?->tanggal_lahir,
            'alamat' => $pasien?->biodata?->alamat_lengkap ?? '-',
            'kontak' => $pasien?->whatsapp_number ?? '-',
            'status_diabetes' => $pp->status_diabetes,
            'pmo' => $pp->pmo
                ? ['nama' => $pp->nama_pmo, 'kontak' => $pp->pmo->whatsapp_number ?? '-']
                : null,
            'kepatuhan' => DashboardRepository::hitungKepatuhanMo($userId),
            'jumlah_cgd' => PengingatCgdLog::query()->forUser($userId)->count(),
            'jadwal_mo' => JadwalMinumObat::query()->active()
                ->whereHas('pasienPmo', fn ($q) => $q->where('id_user', $userId))
                ->with('obat')->get()
                ->map(fn ($j) => [
                    'obat' => $j->obat?->nama ?? '-',
                    'jam' => substr((string) $j->jam_mulai, 0, 5),
                    'frekuensi' => $j->frekuensi_per_hari,
                ])->all(),
            'jadwal_cgd' => PasienRiwayatService::jadwalCgdPasien($userId)['mendatang'],
            'riwayat_mo' => PengingatMoLog::query()->forUser($userId)
                ->orderByDesc('tgl_minum_obat')->orderByDesc('jam_minum_obat')->limit(5)->get(),
            'riwayat_cgd' => PengingatCgdLog::query()->forUser($userId)
                ->orderByDesc('tgl_cgd')->orderByDesc('jam_cgd')->limit(5)->get(),
        ];
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=MasterDirektoriServiceTest`
Expected: PASS (4 tests).

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint app/Services/MasterDirektoriService.php
git add app/Services/MasterDirektoriService.php tests/Feature/MasterDirektori/MasterDirektoriServiceTest.php
git commit -m "feat(master): service direktori pasien (daftar + detail)"
```

---

## Task 2: Master Pasien — controller, route, blade, sidebar

**Files:**
- Create: `app/Http/Controllers/MasterPasienController.php`
- Create: `resources/views/master-pasien/index.blade.php`, `resources/views/master-pasien/show.blade.php`
- Modify: `routes/web.php` (~607–608 + import)
- Modify: `resources/views/components/sidebar.blade.php` (~120, di grup Master Data)
- Test: `tests/Feature/MasterDirektori/MasterPasienTest.php`

**Interfaces:**
- Consumes: `MasterDirektoriService::daftarPasien()`, `detailPasien()` (Task 1).
- Produces: `MasterPasienController::index(Request)`, `show(string $id)`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/MasterDirektori/MasterPasienTest.php`:

```php
<?php

namespace Tests\Feature\MasterDirektori;

use App\Models\PasienPmo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterPasienTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        // superadmin: lolos role:admin,superadmin + bypass permission middleware
        return User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
    }

    private function pasien(string $nama): User
    {
        $p = User::factory()->create(['role' => 'pasien', 'is_active' => true]);
        PasienPmo::create([
            'id_user' => $p->id, 'pmo_user_id' => null, 'nama_pasien' => $nama,
            'nik' => fake()->numerify('################'), 'nama_pmo' => '-',
            'jenis_pmo' => 'Keluarga', 'tanggal_regis' => now()->toDateString(),
            'status_diabetes' => 'Tipe 2', 'is_active' => true,
        ]);

        return $p;
    }

    public function test_admin_melihat_direktori_pasien(): void
    {
        $this->pasien('Siti Aminah');

        $this->actingAs($this->admin())->get('/admin/master/pasien')
            ->assertOk()->assertSee('Siti Aminah');
    }

    public function test_admin_melihat_detail_pasien(): void
    {
        $p = $this->pasien('Siti Aminah');

        $this->actingAs($this->admin())->get("/admin/master/pasien/{$p->id}")
            ->assertOk()->assertSee('Siti Aminah');
    }

    public function test_detail_non_pasien_404(): void
    {
        $bukanPasien = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($this->admin())->get("/admin/master/pasien/{$bukanPasien->id}")
            ->assertNotFound();
    }

    public function test_non_admin_ditolak(): void
    {
        $pasien = User::factory()->create(['role' => 'pasien', 'is_active' => true]);

        $this->actingAs($pasien)->get('/admin/master/pasien')->assertForbidden();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=MasterPasienTest`
Expected: FAIL (route masih `Route::view('placeholder')`; tidak ada "Siti Aminah" / detail belum ada).

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/MasterPasienController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Services\MasterDirektoriService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterPasienController extends Controller
{
    public function index(Request $request): View
    {
        return view('master-pasien.index', [
            'daftar' => MasterDirektoriService::daftarPasien(['cari' => $request->query('cari')]),
            'cari' => $request->query('cari'),
        ]);
    }

    public function show(string $id): View
    {
        $detail = MasterDirektoriService::detailPasien($id);
        abort_if($detail === null, 404);

        return view('master-pasien.show', ['d' => $detail]);
    }
}
```

- [ ] **Step 4: Wire routes**

Di `routes/web.php`, tambahkan import (dekat `use App\Http\Controllers\PasienController;`):

```php
use App\Http\Controllers\MasterPasienController;
```

Ganti baris route `master.pasien` (~607–608) menjadi:

```php
        Route::get('/master/pasien', [MasterPasienController::class, 'index'])
            ->name('master.pasien')->middleware('permission:master-pasien.index');
        Route::get('/master/pasien/{id}', [MasterPasienController::class, 'show'])
            ->name('master.pasien.show')->middleware('permission:master-pasien.show')
            ->where('id', '[0-9a-f\-]+');
```

- [ ] **Step 5: Create the index blade**

Create `resources/views/master-pasien/index.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Master Pasien')

@section('page-header')
    <h4 class="fw-bold mb-1">🧑‍🦽 Master Pasien</h4>
    <small class="text-muted">Direktori pasien terdaftar.</small>
@endsection

@section('content')
    <form method="GET" class="mb-3">
        <div class="input-group" style="max-width: 360px;">
            <input type="text" name="cari" value="{{ $cari }}" class="form-control" placeholder="Cari nama / NIK...">
            <button class="btn btn-primary" type="submit"><i class="ri ri-search-line"></i></button>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr>
                    <th>Nama</th><th>NIK</th><th>Status Diabetes</th><th>PMO</th><th>Kepatuhan</th><th>Status</th><th></th>
                </tr></thead>
                <tbody>
                    @forelse($daftar as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['nama'] }}</td>
                            <td>{{ $row['nik'] }}</td>
                            <td>{{ $row['status_diabetes'] }}</td>
                            <td>{{ $row['nama_pmo'] }}</td>
                            <td><span class="badge bg-info-subtle text-info">{{ $row['kepatuhan'] }}%</span></td>
                            <td>
                                <span class="badge {{ $row['is_active'] ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                    {{ $row['is_active'] ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.master.pasien.show', $row['id_user']) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada pasien terdaftar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $daftar->links() }}</div>
    </div>
@endsection
```

- [ ] **Step 6: Create the show blade**

Create `resources/views/master-pasien/show.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Detail Pasien')

@section('page-header')
    <a href="{{ route('admin.master.pasien') }}" class="small text-decoration-none">&larr; Kembali ke direktori</a>
    <h4 class="fw-bold mb-1 mt-1">{{ $d['nama'] }}</h4>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Profil & PMO</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">NIK</dt><dd class="col-7">{{ $d['nik'] }}</dd>
                        <dt class="col-5">Jenis Kelamin</dt><dd class="col-7">{{ $d['jenis_kelamin'] }}</dd>
                        <dt class="col-5">Tanggal Lahir</dt><dd class="col-7">{{ $d['tanggal_lahir']?->translatedFormat('d F Y') ?? '-' }}</dd>
                        <dt class="col-5">Alamat</dt><dd class="col-7">{{ $d['alamat'] }}</dd>
                        <dt class="col-5">Kontak</dt><dd class="col-7">{{ $d['kontak'] }}</dd>
                        <dt class="col-5">Status Diabetes</dt><dd class="col-7">{{ $d['status_diabetes'] }}</dd>
                        <dt class="col-5">PMO Pendamping</dt>
                        <dd class="col-7">{{ $d['pmo']['nama'] ?? 'Belum ada' }} @if($d['pmo']) <small class="text-muted">({{ $d['pmo']['kontak'] }})</small> @endif</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Ringkasan Kepatuhan</div>
                <div class="card-body d-flex gap-4">
                    <div><div class="h3 fw-bold mb-0">{{ $d['kepatuhan'] }}%</div><small class="text-muted">Kepatuhan obat (30 hari)</small></div>
                    <div><div class="h3 fw-bold mb-0">{{ $d['jumlah_cgd'] }}</div><small class="text-muted">Jumlah cek gula darah</small></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Jadwal Aktif</div>
                <div class="card-body">
                    <h6 class="small text-muted">Minum Obat</h6>
                    <ul class="list-unstyled mb-3">
                        @forelse($d['jadwal_mo'] as $j)
                            <li>💊 {{ $j['obat'] }} — {{ $j['jam'] }} ({{ $j['frekuensi'] }}x/hari)</li>
                        @empty
                            <li class="text-muted">Belum ada jadwal obat aktif.</li>
                        @endforelse
                    </ul>
                    <h6 class="small text-muted">Cek Gula Darah (mendatang)</h6>
                    <ul class="list-unstyled mb-0">
                        @forelse($d['jadwal_cgd'] as $j)
                            <li>🩸 {{ $j['tanggal']->translatedFormat('d M Y') }} — {{ $j['jam'] }} @if($j['tempat']) • {{ $j['tempat'] }} @endif</li>
                        @empty
                            <li class="text-muted">Belum ada jadwal CGD mendatang.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Riwayat Terbaru</div>
                <div class="card-body">
                    <h6 class="small text-muted">Minum Obat</h6>
                    <ul class="list-unstyled mb-3">
                        @forelse($d['riwayat_mo'] as $log)
                            <li>{{ $log->tgl_minum_obat->translatedFormat('d M') }} — {{ $log->nama_obat }} <span class="badge bg-{{ $log->patuh_badge_color }}-subtle text-{{ $log->patuh_badge_color }}">{{ $log->patuh_label }}</span></li>
                        @empty
                            <li class="text-muted">Belum ada riwayat.</li>
                        @endforelse
                    </ul>
                    <h6 class="small text-muted">Cek Gula Darah</h6>
                    <ul class="list-unstyled mb-0">
                        @forelse($d['riwayat_cgd'] as $log)
                            <li>{{ $log->tgl_cgd->translatedFormat('d M') }} — {{ $log->hasil_mgdl }} mg/dL <span class="badge bg-{{ $log->kategori_color }}-subtle text-{{ $log->kategori_color }}">{{ $log->kategori_label }}</span></li>
                        @empty
                            <li class="text-muted">Belum ada riwayat.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
```

- [ ] **Step 7: Add sidebar item**

Di `resources/views/components/sidebar.blade.php`, setelah blok `@can('pasien-pmo.index')` (sekitar baris 129), tambahkan:

```blade
    @can('master-pasien.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.master.pasien') || request()->routeIs('admin.master.pasien.show') ? 'active' : '' }}"
                href="{{ route('admin.master.pasien') }}">
                <span class="nav-icon"><i class="ri ri-user-heart-line"></i></span> Master Pasien
            </a>
        </li>
    @endcan
```

- [ ] **Step 8: Run tests to verify they pass**

Run: `php artisan test --filter=MasterPasienTest`
Expected: PASS (4 tests).

- [ ] **Step 9: Format & commit**

```bash
vendor/bin/pint app/Http/Controllers/MasterPasienController.php
git add app/Http/Controllers/MasterPasienController.php resources/views/master-pasien routes/web.php resources/views/components/sidebar.blade.php tests/Feature/MasterDirektori/MasterPasienTest.php
git commit -m "feat(master): halaman direktori & detail pasien + menu sidebar"
```

---

## Task 3: MasterDirektoriService — bagian PMO

**Files:**
- Modify: `app/Services/MasterDirektoriService.php`
- Test: `tests/Feature/MasterDirektori/MasterDirektoriServiceTest.php` (tambah)

**Interfaces:**
- Produces:
  - `MasterDirektoriService::daftarPmo(array $filter = []): LengthAwarePaginator` — tiap item `['id','nama','kontak','jumlah_binaan','is_active']`.
  - `MasterDirektoriService::detailPmo(string $userId): ?array` — `null` bila bukan user role pmo; berisi `['nama','kontak','is_active','binaan'=>[['nama','status_diabetes','kepatuhan','gd_terakhir'],...]]`.

- [ ] **Step 1: Write the failing test**

Tambahkan ke `tests/Feature/MasterDirektori/MasterDirektoriServiceTest.php`:

```php
    public function test_daftar_pmo_menghitung_binaan(): void
    {
        $pmo = User::factory()->create(['role' => 'pmo', 'is_active' => true]);
        $p = User::factory()->create(['role' => 'pasien', 'is_active' => true]);
        PasienPmo::create([
            'id_user' => $p->id, 'pmo_user_id' => $pmo->id, 'nama_pasien' => 'Pasien A',
            'nik' => fake()->numerify('################'), 'nama_pmo' => $pmo->name,
            'jenis_pmo' => 'Keluarga', 'tanggal_regis' => now()->toDateString(),
            'status_diabetes' => 'Tipe 2', 'is_active' => true,
        ]);

        $page = MasterDirektoriService::daftarPmo();

        $this->assertSame(1, $page->total());
        $this->assertSame(1, $page->items()[0]['jumlah_binaan']);
    }

    public function test_detail_pmo_null_untuk_non_pmo(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->assertNull(MasterDirektoriService::detailPmo($admin->id));
    }

    public function test_detail_pmo_berisi_binaan(): void
    {
        $pmo = User::factory()->create(['role' => 'pmo', 'is_active' => true]);
        $p = User::factory()->create(['role' => 'pasien', 'is_active' => true]);
        PasienPmo::create([
            'id_user' => $p->id, 'pmo_user_id' => $pmo->id, 'nama_pasien' => 'Pasien A',
            'nik' => fake()->numerify('################'), 'nama_pmo' => $pmo->name,
            'jenis_pmo' => 'Keluarga', 'tanggal_regis' => now()->toDateString(),
            'status_diabetes' => 'Tipe 2', 'is_active' => true,
        ]);

        $d = MasterDirektoriService::detailPmo($pmo->id);

        $this->assertSame($pmo->name, $d['nama']);
        $this->assertCount(1, $d['binaan']);
        $this->assertSame('Pasien A', $d['binaan'][0]['nama']);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=MasterDirektoriServiceTest`
Expected: FAIL "Call to undefined method ...daftarPmo()".

- [ ] **Step 3: Implement the PMO methods**

Tambahkan ke `app/Services/MasterDirektoriService.php` (tambah `use App\Models\User;` di atas):

```php
    public static function daftarPmo(array $filter = []): LengthAwarePaginator
    {
        return User::query()->where('role', 'pmo')
            ->when($filter['cari'] ?? null, fn ($q, $c) => $q->where(function ($qq) use ($c) {
                $qq->where('name', 'like', "%{$c}%")
                    ->orWhere('username', 'like', "%{$c}%")
                    ->orWhere('email', 'like', "%{$c}%");
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($u) => [
                'id' => $u->id,
                'nama' => $u->name,
                'kontak' => $u->whatsapp_number ?? '-',
                'jumlah_binaan' => PasienPmo::query()->forPmo($u->id)->active()->count(),
                'is_active' => $u->is_active,
            ]);
    }

    public static function detailPmo(string $userId): ?array
    {
        $pmo = User::query()->where('id', $userId)->where('role', 'pmo')->first();
        if (! $pmo) {
            return null;
        }

        $binaan = DashboardRepository::pasienBinaan($userId)->map(fn ($pp) => [
            'nama' => $pp->nama_pasien,
            'status_diabetes' => $pp->status_diabetes,
            'kepatuhan' => DashboardRepository::hitungKepatuhanMo($pp->id_user),
            'gd_terakhir' => DashboardRepository::hasilGdTerakhir($pp->id_user),
        ])->all();

        return [
            'nama' => $pmo->name,
            'kontak' => $pmo->whatsapp_number ?? '-',
            'is_active' => $pmo->is_active,
            'binaan' => $binaan,
        ];
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=MasterDirektoriServiceTest`
Expected: PASS (semua, termasuk 3 test PMO baru + 4 pasien sebelumnya).

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint app/Services/MasterDirektoriService.php
git add app/Services/MasterDirektoriService.php tests/Feature/MasterDirektori/MasterDirektoriServiceTest.php
git commit -m "feat(master): service direktori PMO (daftar + detail binaan)"
```

---

## Task 4: Master PMO — controller, route, blade, sidebar

**Files:**
- Create: `app/Http/Controllers/MasterPmoController.php`
- Create: `resources/views/master-pmo/index.blade.php`, `resources/views/master-pmo/show.blade.php`
- Modify: `routes/web.php` (~609–610 + import)
- Modify: `resources/views/components/sidebar.blade.php` (setelah item Master Pasien)
- Test: `tests/Feature/MasterDirektori/MasterPmoTest.php`

**Interfaces:**
- Consumes: `MasterDirektoriService::daftarPmo()`, `detailPmo()` (Task 3).
- Produces: `MasterPmoController::index(Request)`, `show(string $id)`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/MasterDirektori/MasterPmoTest.php`:

```php
<?php

namespace Tests\Feature\MasterDirektori;

use App\Models\PasienPmo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterPmoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
    }

    public function test_admin_melihat_direktori_pmo(): void
    {
        User::factory()->create(['role' => 'pmo', 'is_active' => true, 'name' => 'Budi PMO']);

        $this->actingAs($this->admin())->get('/admin/master/pmo')
            ->assertOk()->assertSee('Budi PMO');
    }

    public function test_admin_melihat_detail_pmo(): void
    {
        $pmo = User::factory()->create(['role' => 'pmo', 'is_active' => true, 'name' => 'Budi PMO']);

        $this->actingAs($this->admin())->get("/admin/master/pmo/{$pmo->id}")
            ->assertOk()->assertSee('Budi PMO');
    }

    public function test_detail_non_pmo_404(): void
    {
        $bukanPmo = User::factory()->create(['role' => 'pasien', 'is_active' => true]);

        $this->actingAs($this->admin())->get("/admin/master/pmo/{$bukanPmo->id}")
            ->assertNotFound();
    }

    public function test_non_admin_ditolak(): void
    {
        $pasien = User::factory()->create(['role' => 'pasien', 'is_active' => true]);

        $this->actingAs($pasien)->get('/admin/master/pmo')->assertForbidden();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=MasterPmoTest`
Expected: FAIL (route masih placeholder).

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/MasterPmoController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Services\MasterDirektoriService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterPmoController extends Controller
{
    public function index(Request $request): View
    {
        return view('master-pmo.index', [
            'daftar' => MasterDirektoriService::daftarPmo(['cari' => $request->query('cari')]),
            'cari' => $request->query('cari'),
        ]);
    }

    public function show(string $id): View
    {
        $detail = MasterDirektoriService::detailPmo($id);
        abort_if($detail === null, 404);

        return view('master-pmo.show', ['d' => $detail]);
    }
}
```

- [ ] **Step 4: Wire routes**

Di `routes/web.php`, tambahkan import:

```php
use App\Http\Controllers\MasterPmoController;
```

Ganti baris route `master.pmo` (~609–610) menjadi:

```php
        Route::get('/master/pmo', [MasterPmoController::class, 'index'])
            ->name('master.pmo')->middleware('permission:master-pmo.index');
        Route::get('/master/pmo/{id}', [MasterPmoController::class, 'show'])
            ->name('master.pmo.show')->middleware('permission:master-pmo.show')
            ->where('id', '[0-9a-f\-]+');
```

- [ ] **Step 5: Create the index blade**

Create `resources/views/master-pmo/index.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Master PMO')

@section('page-header')
    <h4 class="fw-bold mb-1">👥 Master PMO</h4>
    <small class="text-muted">Direktori Pendamping Minum Obat.</small>
@endsection

@section('content')
    <form method="GET" class="mb-3">
        <div class="input-group" style="max-width: 360px;">
            <input type="text" name="cari" value="{{ $cari }}" class="form-control" placeholder="Cari nama / email...">
            <button class="btn btn-primary" type="submit"><i class="ri ri-search-line"></i></button>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr>
                    <th>Nama PMO</th><th>Kontak</th><th>Pasien Binaan</th><th>Status</th><th></th>
                </tr></thead>
                <tbody>
                    @forelse($daftar as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['nama'] }}</td>
                            <td>{{ $row['kontak'] }}</td>
                            <td><span class="badge bg-info-subtle text-info">{{ $row['jumlah_binaan'] }}</span></td>
                            <td>
                                <span class="badge {{ $row['is_active'] ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                    {{ $row['is_active'] ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.master.pmo.show', $row['id']) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada PMO terdaftar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $daftar->links() }}</div>
    </div>
@endsection
```

- [ ] **Step 6: Create the show blade**

Create `resources/views/master-pmo/show.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Detail PMO')

@section('page-header')
    <a href="{{ route('admin.master.pmo') }}" class="small text-decoration-none">&larr; Kembali ke direktori</a>
    <h4 class="fw-bold mb-1 mt-1">{{ $d['nama'] }}</h4>
@endsection

@section('content')
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Profil PMO</div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-3">Kontak</dt><dd class="col-9">{{ $d['kontak'] }}</dd>
                <dt class="col-3">Status</dt>
                <dd class="col-9">
                    <span class="badge {{ $d['is_active'] ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                        {{ $d['is_active'] ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </dd>
            </dl>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Pasien Binaan</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Nama</th><th>Status Diabetes</th><th>Kepatuhan</th><th>GD Terakhir</th></tr></thead>
                <tbody>
                    @forelse($d['binaan'] as $b)
                        <tr>
                            <td class="fw-semibold">{{ $b['nama'] }}</td>
                            <td>{{ $b['status_diabetes'] }}</td>
                            <td><span class="badge bg-info-subtle text-info">{{ $b['kepatuhan'] }}%</span></td>
                            <td>{{ $b['gd_terakhir'] !== null ? $b['gd_terakhir'].' mg/dL' : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada pasien binaan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
```

- [ ] **Step 7: Add sidebar item**

Di `resources/views/components/sidebar.blade.php`, tepat setelah blok `@can('master-pasien.index')` (dari Task 2), tambahkan:

```blade
    @can('master-pmo.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.master.pmo') || request()->routeIs('admin.master.pmo.show') ? 'active' : '' }}"
                href="{{ route('admin.master.pmo') }}">
                <span class="nav-icon"><i class="ri ri-parent-line"></i></span> Master PMO
            </a>
        </li>
    @endcan
```

- [ ] **Step 8: Run tests + full suite**

Run: `php artisan test --filter=MasterPmoTest` → PASS (4 tests).
Run: `php artisan test --filter=MasterDirektori` → semua hijau.
Run: `composer test` → tidak ada regresi baru (2 test auth Breeze yang memang pre-existing boleh tetap merah).

- [ ] **Step 9: Format & commit**

```bash
vendor/bin/pint
git add app/Http/Controllers/MasterPmoController.php resources/views/master-pmo routes/web.php resources/views/components/sidebar.blade.php tests/Feature/MasterDirektori/MasterPmoTest.php
git commit -m "feat(master): halaman direktori & detail PMO + menu sidebar"
```

---

## Self-Review (sudah dijalankan penulis plan)

**Spec coverage:** Direktori pasien (Task 2), detail pasien dgn profil/PMO/kepatuhan/jadwal/riwayat (Task 1 service + Task 2 blade), direktori PMO (Task 4), detail PMO dgn binaan (Task 3 service + Task 4 blade), sidebar + route gated permission (Task 2 & 4), pengujian (tiap task), aturan 404 non-pasien/non-pmo (Task 1/3 service + Task 2/4 controller). Semua tercakup.

**Placeholder scan:** Tidak ada TODO/TBD pada langkah kode.

**Type consistency:** Kunci array `daftarPasien` (`id_user/nama/nik/status_diabetes/nama_pmo/kepatuhan/is_active`) konsisten antara Task 1 & blade Task 2; `detailPasien` (`nama/nik/jenis_kelamin/tanggal_lahir/alamat/kontak/status_diabetes/pmo/kepatuhan/jumlah_cgd/jadwal_mo/jadwal_cgd/riwayat_mo/riwayat_cgd`) konsisten Task 1 ↔ Task 2; `daftarPmo` (`id/nama/kontak/jumlah_binaan/is_active`) & `detailPmo` (`nama/kontak/is_active/binaan[]`) konsisten Task 3 ↔ Task 4; nama route (`admin.master.pasien`, `admin.master.pasien.show`, `admin.master.pmo`, `admin.master.pmo.show`) konsisten; reuse signatures (`hitungKepatuhanMo`, `pasienBinaan`, `hasilGdTerakhir`, `jadwalCgdPasien`) sesuai yang ada.

## Catatan verifikasi saat eksekusi

- `User` punya kolom `whatsapp_number`, `is_active`, `role` (string enum). Cek bila query `where('role','pmo')` perlu penyesuaian enum (umumnya tidak — tersimpan sebagai string).
- `PasienPmo::scopeSearch` mencari `nama_pasien/nama_pmo/nik`; `scopeActive`, `scopeForPmo` ada.
- `User->biodata` (`UserBiodata`) accessor `jenis_kelamin_label`, `alamat_lengkap`, cast `tanggal_lahir`→date.
- `LengthAwarePaginator::through()` memetakan item sambil mempertahankan pagination (Laravel 9+).
- `layouts.app` menyediakan section `title`, `page-header`, `content`.
