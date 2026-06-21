# POV Switcher (Impersonation) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Superadmin bisa 1-klik "Lihat sebagai" Admin/PMO/Pasien (impersonate user wakil) untuk melihat POV sistem role itu, dengan banner + tombol kembali.

**Architecture:** Impersonation berbasis session custom: simpan `impersonator_id` (id superadmin asli) lalu `Auth::login(targetUser)`; kembali = `Auth::login(superadmin asli)` + hapus key. `Auth::user()` benar-benar menjadi user target → role/permission/data otomatis tepat. Service statis + controller tipis + UI (tombol dropdown header + banner layout).

**Tech Stack:** Laravel 12, PHP 8.2, MySQL (test: sqlite :memory:), Blade + Bootstrap, Spatie permission, custom `role:` middleware.

## Global Constraints

- Domain language Indonesian (nama method, komentar, pesan, string UI).
- Services memakai **static methods** eksklusif.
- Hanya role yang bisa diimpersonate: `admin`, `pmo`, `pasien` — TIDAK PERNAH `superadmin`.
- Hak memulai POV digate `isSuperadmin()` (bukan id tertentu) → mendukung >1 superadmin.
- Wakil = `User::where('role',$r)->where('is_active',true)->orderBy('created_at')->first()`.
- Mode penuh (login-as sungguhan); tidak ada read-only.
- Route `leave` HANYA middleware `auth` (tanpa `verified`) agar selalu bisa keluar.
- Test dijalankan `php artisan test` (sqlite :memory:).
- Format `vendor/bin/pint` sebelum commit. Jangan push kecuali diminta.

---

### Task 1: Service + Controller + Routes (backend)

**Files:**
- Create: `app/Services/ImpersonationService.php`
- Create: `app/Http/Controllers/ImpersonationController.php`
- Modify: `routes/web.php` (import + route group)
- Test: `tests/Feature/Impersonation/ImpersonationTest.php`

**Interfaces:**
- Produces:
  - `ImpersonationService::SESSION_KEY` (string `'impersonator_id'`)
  - `ImpersonationService::mulai(string $roleValue): User` — validasi role ∈ {admin,pmo,pasien}; pilih wakil; bila tak ada lempar `\RuntimeException`; bila role invalid lempar `\InvalidArgumentException`; set session + `Auth::login(target)`; return target.
  - `ImpersonationService::kembali(): void` — restore superadmin asli dari session, hapus key (aman bila key hilang).
  - `ImpersonationService::sedangImpersonate(): bool`
  - `ImpersonationService::impersonator(): ?User`
  - Routes: `impersonate.start` (POST `/impersonate/{role}`, where role `admin|pmo|pasien`, middleware `auth,verified,role:superadmin`), `impersonate.leave` (POST `/impersonate/leave`, middleware `auth`).

- [ ] **Step 1: Tulis test yang gagal**

Create `tests/Feature/Impersonation/ImpersonationTest.php`:

```php
<?php

namespace Tests\Feature\Impersonation;

use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private function buatUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    public function test_superadmin_bisa_mulai_pov_pasien(): void
    {
        $super = $this->buatUser('superadmin');
        $pasien = $this->buatUser('pasien');

        $res = $this->actingAs($super)->post('/impersonate/pasien');

        $res->assertRedirect(route('pasien.dashboard'));
        $this->assertAuthenticatedAs($pasien);
        $this->assertSame($super->id, session(ImpersonationService::SESSION_KEY));
    }

    public function test_non_superadmin_tidak_bisa_mulai(): void
    {
        $admin = $this->buatUser('admin');
        $this->buatUser('pasien');

        $res = $this->actingAs($admin)->post('/impersonate/pasien');

        $res->assertForbidden();
        $this->assertAuthenticatedAs($admin); // sesi tak berubah
    }

    public function test_role_superadmin_ditolak_oleh_route(): void
    {
        $super = $this->buatUser('superadmin');

        $res = $this->actingAs($super)->post('/impersonate/superadmin');

        $res->assertNotFound(); // regex route hanya admin|pmo|pasien
    }

    public function test_service_menolak_role_invalid(): void
    {
        $this->buatUser('superadmin');
        $this->expectException(\InvalidArgumentException::class);

        ImpersonationService::mulai('superadmin');
    }

    public function test_wakil_kosong_redirect_dengan_error(): void
    {
        $super = $this->buatUser('superadmin');
        // tidak ada user pmo

        $res = $this->actingAs($super)->post('/impersonate/pmo');

        $res->assertRedirect();
        $res->assertSessionHas('error');
        $this->assertAuthenticatedAs($super);
        $this->assertNull(session(ImpersonationService::SESSION_KEY));
    }

    public function test_kembali_memulihkan_superadmin(): void
    {
        $super = $this->buatUser('superadmin');
        $pasien = $this->buatUser('pasien');

        $this->actingAs($super)->post('/impersonate/pasien');
        $this->assertAuthenticatedAs($pasien);

        $res = $this->post('/impersonate/leave');

        $res->assertRedirect(route('superadmin.dashboard'));
        $this->assertAuthenticatedAs($super);
        $this->assertNull(session(ImpersonationService::SESSION_KEY));
    }

    public function test_superadmin_kedua_juga_bisa(): void
    {
        $this->buatUser('superadmin'); // superadmin pertama
        $super2 = $this->buatUser('superadmin');
        $pasien = $this->buatUser('pasien');

        $res = $this->actingAs($super2)->post('/impersonate/pasien');

        $res->assertRedirect(route('pasien.dashboard'));
        $this->assertAuthenticatedAs($pasien);
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --filter=ImpersonationTest`
Expected: FAIL (route `/impersonate/*` belum ada / class service belum ada).

- [ ] **Step 3: Buat service**

Create `app/Services/ImpersonationService.php`:

```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ImpersonationService
{
    public const SESSION_KEY = 'impersonator_id';

    /** Role yang boleh diimpersonate (tidak pernah superadmin). */
    private const ROLE_BOLEH = ['admin', 'pmo', 'pasien'];

    /**
     * Mulai mode POV: jadi user wakil dari role tujuan.
     *
     * @throws \InvalidArgumentException role tidak valid
     * @throws \RuntimeException tak ada user aktif untuk role itu
     */
    public static function mulai(string $roleValue): User
    {
        if (! in_array($roleValue, self::ROLE_BOLEH, true)) {
            throw new \InvalidArgumentException('Role tidak valid untuk mode POV.');
        }

        $target = User::query()
            ->where('role', $roleValue)
            ->where('is_active', true)
            ->orderBy('created_at')
            ->first();

        if (! $target) {
            throw new \RuntimeException('Belum ada user aktif untuk role tersebut.');
        }

        $asalId = Auth::id();
        session([self::SESSION_KEY => $asalId]);
        Auth::login($target);

        Log::info('[impersonate] mulai', [
            'oleh' => $asalId, 'menjadi' => $target->id, 'role' => $roleValue,
        ]);

        return $target;
    }

    /** Kembali ke superadmin asli. Aman bila key sudah hilang. */
    public static function kembali(): void
    {
        $asalId = session(self::SESSION_KEY);
        session()->forget(self::SESSION_KEY);

        if (! $asalId) {
            return;
        }

        $asal = User::find($asalId);
        if ($asal) {
            Auth::login($asal);
            Log::info('[impersonate] kembali', ['ke' => $asal->id]);
        }
    }

    public static function sedangImpersonate(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    public static function impersonator(): ?User
    {
        $id = session(self::SESSION_KEY);

        return $id ? User::find($id) : null;
    }
}
```

- [ ] **Step 4: Buat controller**

Create `app/Http/Controllers/ImpersonationController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Services\ImpersonationService;
use Illuminate\Http\RedirectResponse;

class ImpersonationController extends Controller
{
    public function mulai(string $role): RedirectResponse
    {
        $user = auth()->user();

        // Belt-and-suspenders: route sudah digate role:superadmin.
        if (! $user || ! $user->isSuperadmin() || ImpersonationService::sedangImpersonate()) {
            abort(403);
        }

        try {
            $target = ImpersonationService::mulai($role);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route($target->homeRoute())
            ->with('success', 'Sekarang melihat sebagai '.$target->name.' ('.$target->role->label().').');
    }

    public function kembali(): RedirectResponse
    {
        if (! ImpersonationService::sedangImpersonate()) {
            return redirect()->route('dashboard');
        }

        ImpersonationService::kembali();

        return redirect()->route(auth()->user()->homeRoute())
            ->with('success', 'Kembali ke akun Super Admin.');
    }
}
```

- [ ] **Step 5: Tambah routes**

Modify `routes/web.php`.

Tambah import (dekat baris `use App\Http\Controllers\JadwalCgdController;`):

```php
use App\Http\Controllers\ImpersonationController;
```

Tambah grup route (di dalam file, scope mana pun di top-level; mis. setelah grup `pengaturan-pengingat`):

```php
Route::prefix('impersonate')->name('impersonate.')->group(function () {
    // leave: HANYA 'auth' (tanpa 'verified') agar superadmin selalu bisa keluar
    // walau user target belum terverifikasi.
    Route::middleware('auth')
        ->post('/leave', [ImpersonationController::class, 'kembali'])->name('leave');

    Route::middleware(['auth', 'verified', 'role:superadmin'])
        ->post('/{role}', [ImpersonationController::class, 'mulai'])->name('start')
        ->where('role', 'admin|pmo|pasien');
});
```

- [ ] **Step 6: Jalankan test, pastikan lulus**

Run: `php artisan test --filter=ImpersonationTest`
Expected: PASS (7 test).

- [ ] **Step 7: Format & commit**

```bash
vendor/bin/pint app/Services/ImpersonationService.php app/Http/Controllers/ImpersonationController.php
git add app/Services/ImpersonationService.php app/Http/Controllers/ImpersonationController.php routes/web.php tests/Feature/Impersonation/ImpersonationTest.php
git commit -m "feat(impersonate): service, controller & route POV switcher superadmin"
```

---

### Task 2: UI — tombol "Lihat sebagai" + banner POV

**Files:**
- Modify: `resources/views/partials/header.blade.php` (grup dropdown "Lihat sebagai")
- Modify: `resources/views/layouts/app.blade.php` (banner mode POV)

**Interfaces:**
- Consumes: routes `impersonate.start`/`impersonate.leave`; `ImpersonationService::sedangImpersonate()`; `auth()->user()->isSuperadmin()`, `->name`, `->role->label()`.

Catatan: tidak ada test otomatis untuk Blade — verifikasi manual di Step terakhir.

- [ ] **Step 1: Tambah grup "Lihat sebagai" di dropdown header**

Modify `resources/views/partials/header.blade.php`. Sisipkan SEBELUM blok Logout (`<li><hr class="dropdown-divider my-1"></li>` yang mendahului form logout, sekitar baris 49), TEPAT setelah item "Profil Saya" (`</li>` penutupnya di baris 48):

```blade
                    @if (auth()->user()->isSuperadmin())
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><h6 class="dropdown-header text-uppercase small text-muted mb-0">Lihat sebagai</h6></li>
                        @foreach (['admin' => 'Admin', 'pmo' => 'PMO', 'pasien' => 'Pasien'] as $rv => $lbl)
                            <li>
                                <form method="POST" action="{{ route('impersonate.start', $rv) }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item py-2">
                                        <i class="ri ri-eye-line me-2"></i> {{ $lbl }}
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    @endif
```

- [ ] **Step 2: Tambah banner mode POV di layout**

Modify `resources/views/layouts/app.blade.php`. TEPAT setelah `@include('partials.header')` (baris 55), sebelum `<div class="body flex-grow-1 px-3 py-4">`, sisipkan:

```blade
        @if (\App\Services\ImpersonationService::sedangImpersonate())
            <div class="alert alert-warning border-0 rounded-0 mb-0 d-flex align-items-center justify-content-between px-3 py-2">
                <span class="small">
                    <i class="ri ri-eye-line me-1"></i>
                    <strong>Mode POV</strong> — Anda melihat sebagai
                    <strong>{{ auth()->user()->name }}</strong>
                    ({{ auth()->user()->role?->label() }}).
                </span>
                <form method="POST" action="{{ route('impersonate.leave') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-dark">
                        <i class="ri ri-arrow-go-back-line me-1"></i> Kembali ke Super Admin
                    </button>
                </form>
            </div>
        @endif
```

- [ ] **Step 3: Build asset & verifikasi manual**

Run: `npm run build`
Expected: build sukses tanpa error.

Verifikasi manual:
1. Login superadmin → buka dropdown user → muncul grup "Lihat sebagai" (Admin/PMO/Pasien).
2. Klik "Pasien" → diarahkan ke dashboard pasien; banner kuning "Mode POV — Anda melihat sebagai ..." muncul di atas; sidebar/menu berubah jadi POV pasien.
3. Klik "Kembali ke Super Admin" → kembali ke dashboard superadmin; banner hilang; dropdown "Lihat sebagai" muncul lagi.
4. Saat mode POV, grup "Lihat sebagai" TIDAK muncul (karena current user bukan superadmin).
5. Login sebagai admin biasa → grup "Lihat sebagai" tidak ada.

- [ ] **Step 4: Commit**

```bash
git add resources/views/partials/header.blade.php resources/views/layouts/app.blade.php
git commit -m "feat(impersonate): tombol Lihat sebagai di header + banner mode POV"
```

---

### Task 3: Verifikasi menyeluruh

**Files:** (tidak ada perubahan kode; gerbang akhir)

- [ ] **Step 1: Jalankan seluruh test**

Run: `php artisan test`
Expected: Semua hijau kecuali 2 test auth Breeze pre-existing (memori `auth-tests-pre-existing-fail`). Tidak ada regresi baru.

- [ ] **Step 2: Lint**

Run: `vendor/bin/pint --test`
Expected: file fitur ini bersih (abaikan temuan pre-existing di `app_backup_before_mysql/` & file tak disentuh).

- [ ] **Step 3: Commit penutup (bila ada perubahan format)**

```bash
git add -A
git commit -m "chore(impersonate): rapikan format & verifikasi akhir"
```

---

## Self-Review

- **Spec coverage:** service mulai/kembali/sedangImpersonate/impersonator + pilih wakil + tolak superadmin (T1) ✓; controller gerbang + redirect homeRoute + flash error (T1) ✓; routes start(role:superadmin)/leave(auth-only) (T1) ✓; tombol dropdown superadmin (T2) ✓; banner + kembali (T2) ✓; multi-superadmin (T1 test) ✓; edge wakil kosong & role superadmin ditolak (T1 test) ✓. Audit Log::info ✓.
- **Placeholder scan:** tidak ada TBD/TODO; semua step berisi kode/perintah konkret.
- **Type consistency:** `ImpersonationService::SESSION_KEY`, `mulai(string):User`, `kembali():void`, `sedangImpersonate():bool`, `impersonator():?User` konsisten dipakai di controller/blade/test. Route name `impersonate.start`/`impersonate.leave` konsisten. `homeRoute()`/`role->label()` sesuai User/UserRole yang ada.
