# POV Switcher — Pindah Role Langsung Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Saat ber-POV (impersonate), opsi "Lihat sebagai" tetap muncul dan superadmin bisa pindah langsung ke role lain tanpa kembali ke superadmin dulu.

**Architecture:** Otorisasi berbasis "operator" (superadmin asli = `impersonator()` saat POV, else current user). Service `mulaiSebagai(operator, role)` dipakai untuk start & switch; `impersonator_id` selalu = id operator superadmin (titik kembali tak pernah menunjuk user yang ditiru). Route `start` digate `auth` saja; otorisasi superadmin dilakukan di controller. UI: grup "Lihat sebagai" tampil saat superadmin ATAU sedang impersonate, dengan badge "aktif" pada role berjalan.

**Tech Stack:** Laravel 12, PHP 8.2, MySQL (test: sqlite :memory:), Blade + Bootstrap.

## Global Constraints

- Domain language Indonesian (method, komentar, string UI).
- Services memakai **static methods** eksklusif.
- Hanya role `admin|pmo|pasien` yang bisa diimpersonate, TIDAK PERNAH `superadmin`.
- Otorisasi: hanya **operator superadmin** (langsung, atau via impersonation yang ia mulai)
  boleh start/switch. `impersonator_id` selalu = id operator superadmin.
- `leave` route tetap `auth`-only (sudah ada).
- Test dijalankan `php artisan test` (sqlite :memory:).
- Format `vendor/bin/pint` sebelum commit. Jangan push kecuali diminta.

---

### Task 1: Backend — operator-based authorization + switch

**Files:**
- Modify: `app/Services/ImpersonationService.php`
- Modify: `app/Http/Controllers/ImpersonationController.php`
- Modify: `routes/web.php` (middleware route `start`)
- Modify: `tests/Feature/Impersonation/ImpersonationTest.php`

**Interfaces:**
- Produces:
  - `ImpersonationService::mulaiSebagai(User $operator, string $roleValue): User` — validasi role
    (`\InvalidArgumentException`), cari wakil (`\RuntimeException`), `Auth::login(target)` lalu
    `session([SESSION_KEY => operator->id])`, log, return target. (Menggantikan `mulai(string)`.)
  - `kembali():bool`, `sedangImpersonate():bool`, `impersonator():?User` (tetap).
  - Controller `mulai(string $role)` kini menghitung operator & otorisasi superadmin sendiri.
  - Route `impersonate.start` middleware `auth` saja.

- [ ] **Step 1: Perbarui test (failing)**

Edit `tests/Feature/Impersonation/ImpersonationTest.php`:

(a) Ganti method `test_service_menolak_role_invalid` menjadi:

```php
    public function test_service_menolak_role_invalid(): void
    {
        $super = $this->buatUser('superadmin');
        $this->expectException(\InvalidArgumentException::class);

        ImpersonationService::mulaiSebagai($super, 'superadmin');
    }
```

(b) HAPUS seluruh method `test_service_tolak_mulai_saat_sudah_impersonate` (pindah kini sah).

(c) Tambah method baru:

```php
    public function test_bisa_pindah_role_langsung_saat_pov(): void
    {
        $super = $this->buatUser('superadmin');
        $this->buatUser('pasien');
        $admin = $this->buatUser('admin');

        // mulai POV sebagai pasien
        $this->actingAs($super)->post('/impersonate/pasien');

        // pindah langsung ke admin tanpa kembali ke superadmin
        $res = $this->post('/impersonate/admin');

        $res->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
        // titik kembali tetap superadmin asli, bukan pasien
        $this->assertSame($super->id, session(ImpersonationService::SESSION_KEY));
    }
```

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --filter=ImpersonationTest`
Expected: FAIL (`mulaiSebagai` belum ada; pindah role saat POV masih 403 karena route `role:superadmin`).

- [ ] **Step 3: Ganti `mulai()` jadi `mulaiSebagai()` di service**

Modify `app/Services/ImpersonationService.php`. Ganti SELURUH method `mulai()` (baris ~16–51,
termasuk docblock & guard `\LogicException`) dengan:

```php
    /**
     * Mulai/pindah mode POV: jadi user wakil dari role tujuan, dengan
     * $operator (superadmin asli) sebagai titik kembali.
     *
     * @throws \InvalidArgumentException role tidak valid
     * @throws \RuntimeException tak ada user aktif untuk role itu
     */
    public static function mulaiSebagai(User $operator, string $roleValue): User
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

        Auth::login($target);
        session([self::SESSION_KEY => $operator->id]);

        Log::info('[impersonate] mulai', [
            'oleh' => $operator->id, 'menjadi' => $target->id, 'role' => $roleValue,
        ]);

        return $target;
    }
```

(Method `kembali`, `sedangImpersonate`, `impersonator` TIDAK diubah.)

- [ ] **Step 4: Perbarui controller `mulai()`**

Modify `app/Http/Controllers/ImpersonationController.php`. Ganti method `mulai()` dengan:

```php
    public function mulai(string $role): RedirectResponse
    {
        // Operator = superadmin asli: saat ber-POV ambil dari session, selain itu current user.
        $operator = ImpersonationService::sedangImpersonate()
            ? ImpersonationService::impersonator()
            : auth()->user();

        if (! $operator || ! $operator->isSuperadmin()) {
            abort(403);
        }

        try {
            $target = ImpersonationService::mulaiSebagai($operator, $role);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route($target->homeRoute())
            ->with('success', 'Sekarang melihat sebagai '.$target->name.' ('.$target->role->label().').');
    }
```

(Method `kembali()` TIDAK diubah.)

- [ ] **Step 5: Longgarkan middleware route `start`**

Modify `routes/web.php`. Pada grup `impersonate` (sekitar baris 465–467), ganti:

```php
    Route::middleware(['auth', 'verified', 'role:superadmin'])
        ->post('/{role}', [ImpersonationController::class, 'mulai'])->name('start')
        ->where('role', 'admin|pmo|pasien');
```

menjadi:

```php
    // Otorisasi superadmin/operator dilakukan di controller (saat ber-POV current
    // user bukan superadmin, jadi tak bisa pakai middleware role:superadmin di sini).
    Route::middleware('auth')
        ->post('/{role}', [ImpersonationController::class, 'mulai'])->name('start')
        ->where('role', 'admin|pmo|pasien');
```

(Route `leave` TIDAK diubah.)

- [ ] **Step 6: Jalankan test, pastikan lulus**

Run: `php artisan test --filter=ImpersonationTest`
Expected: PASS. (8 test: start pasien, non-superadmin 403, role superadmin 404, role invalid via service, wakil kosong, leave, superadmin kedua, deleted-superadmin, pindah-langsung — total 9 setelah hapus 1 & tambah 1 = 9.)

- [ ] **Step 7: Format & commit**

```bash
vendor/bin/pint app/Services/ImpersonationService.php app/Http/Controllers/ImpersonationController.php
git add app/Services/ImpersonationService.php app/Http/Controllers/ImpersonationController.php routes/web.php tests/Feature/Impersonation/ImpersonationTest.php
git commit -m "feat(impersonate): otorisasi berbasis operator + pindah role langsung saat POV"
```

---

### Task 2: UI — opsi tetap muncul saat POV + badge aktif

**Files:**
- Modify: `resources/views/partials/header.blade.php`

**Interfaces:**
- Consumes: `ImpersonationService::sedangImpersonate()`, `auth()->user()->isSuperadmin()`,
  `auth()->user()->role?->value`, route `impersonate.start`.

Catatan: tidak ada test otomatis untuk Blade — verifikasi manual di Step terakhir.

- [ ] **Step 1: Longgarkan tampil grup + tandai role aktif**

Modify `resources/views/partials/header.blade.php`. Cari blok grup "Lihat sebagai" (diawali
`@if (auth()->user()->isSuperadmin())` lalu header "Lihat sebagai" dan `@foreach (['admin' => 'Admin', ...])`).
Ganti SELURUH blok itu (`@if (...) ... @endif`) dengan:

```blade
                    @if (auth()->user()->isSuperadmin() || \App\Services\ImpersonationService::sedangImpersonate())
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><h6 class="dropdown-header text-uppercase small text-muted mb-0">Lihat sebagai</h6></li>
                        @foreach (['admin' => 'Admin', 'pmo' => 'PMO', 'pasien' => 'Pasien'] as $rv => $lbl)
                            <li>
                                <form method="POST" action="{{ route('impersonate.start', $rv) }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item py-2 d-flex align-items-center">
                                        <i class="ri ri-eye-line me-2"></i> {{ $lbl }}
                                        @if (auth()->user()->role?->value === $rv)
                                            <span class="badge bg-success ms-auto">aktif</span>
                                        @endif
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    @endif
```

- [ ] **Step 2: Build asset & verifikasi manual**

Run: `npm run build`
Expected: build sukses tanpa error.

Verifikasi manual:
1. Login superadmin → dropdown user → grup "Lihat sebagai" (Admin/PMO/Pasien), tanpa badge.
2. Klik "Pasien" → POV pasien; banner muncul; buka dropdown → grup "Lihat sebagai" MASIH ADA,
   item "Pasien" ber-badge **aktif**.
3. Dari POV pasien klik "Admin" → langsung pindah ke POV admin (tak perlu kembali dulu);
   item "Admin" kini ber-badge aktif; banner tetap.
4. Klik "Kembali ke Super Admin" (banner) → kembali ke superadmin; badge hilang.
5. Login admin biasa (tanpa POV) → grup "Lihat sebagai" tidak muncul.

- [ ] **Step 3: Commit**

```bash
git add resources/views/partials/header.blade.php
git commit -m "feat(impersonate): opsi Lihat sebagai tetap muncul saat POV + badge role aktif"
```

---

### Task 3: Verifikasi menyeluruh

**Files:** (tidak ada perubahan kode; gerbang akhir)

- [ ] **Step 1: Jalankan seluruh test**

Run: `php artisan test`
Expected: Semua hijau kecuali 2 test auth Breeze pre-existing (memori `auth-tests-pre-existing-fail`).

- [ ] **Step 2: Lint**

Run: `vendor/bin/pint --test`
Expected: file fitur ini bersih (abaikan pre-existing `app_backup_before_mysql/` & file tak disentuh).

- [ ] **Step 3: Commit penutup (bila ada perubahan format)**

```bash
git add -A
git commit -m "chore(impersonate): rapikan format & verifikasi akhir"
```

---

## Self-Review

- **Spec coverage:** `mulaiSebagai` + hapus `mulai`/guard (T1) ✓; controller operator-based + 403
  non-operator (T1) ✓; route `start` jadi `auth`-only (T1) ✓; `impersonator_id` selalu operator
  superadmin (T1 method + test pindah) ✓; test role-invalid disesuaikan + test double-guard
  dihapus + test pindah ditambah (T1) ✓; UI grup tampil saat superadmin||sedangImpersonate +
  badge aktif (T2) ✓; verifikasi (T3) ✓.
- **Placeholder scan:** tidak ada TBD/TODO; semua step berisi kode/perintah konkret.
- **Type consistency:** `mulaiSebagai(User,string):User` dipakai konsisten di service/controller/test;
  `sedangImpersonate()`/`impersonator()` tetap; route name `impersonate.start` konsisten; field
  `role?->value` sesuai `UserRole` enum.
