# Desain: POV Switcher (Impersonation) untuk Superadmin

Tanggal: 2026-06-21
Status: disetujui (siap rencana implementasi)

## Latar belakang

Superadmin perlu melihat "sudut pandang sistem" dari role lain (admin, pmo, pasien) untuk
verifikasi/menu/data. Menukar enum role saja TIDAK cukup: layar pasien/pmo digerakkan data
milik user lewat `Auth::id()` (biodata, jadwal, binaan, mapping `pasien_pmo`), dan
`User::hasPermissionTo()` selalu `true` untuk superadmin — sehingga sekadar ganti role akan
menampilkan layar kosong/rusak dengan permission yang tetap lolos semua. POV otentik hanya
didapat dengan **menjadi user nyata** dari role itu (impersonation / login-as sementara).

## Keputusan desain (dikonfirmasi user)

1. **Cara pilih POV:** 1-klik per role — tiga aksi: Lihat sebagai Admin / PMO / Pasien.
2. **Wakil:** sistem memilih user **aktif tertua** (`is_active=true`, urut `created_at`) dengan
   role itu. Bila tak ada → pesan error, tidak masuk mode POV.
3. **Mode:** penuh (login-as sungguhan) — aksi tulis user itu berlaku nyata.
4. **Multi-superadmin:** hak POV switcher digate oleh `isSuperadmin()` (bukan id tertentu),
   jadi semua superadmin (boleh lebih dari satu) memilikinya. Role yang bisa diimpersonate
   hanya `admin|pmo|pasien` — TIDAK pernah `superadmin` (POV-nya identik, tak perlu).

## Arsitektur

Impersonation berbasis **session** (custom, ringan — tanpa package eksternal). Cocok dengan
auth ganda (enum `UserRole` + Spatie): saat impersonate, `Auth::user()` benar-benar menjadi
user target, sehingga role, permission (override superadmin tidak lagi berlaku karena current
user bukan superadmin), `homeRoute()`, dan semua query `Auth::id()` otomatis tepat.

### 1. Service — `ImpersonationService` (static)

- `mulai(string $roleValue): User` — validasi `$roleValue ∈ {admin,pmo,pasien}`; cari wakil
  (`User::where('role',$roleValue)->where('is_active',true)->orderBy('created_at')->first()`);
  bila null → lempar exception domain (ditangkap controller). Simpan
  `session(['impersonator_id' => Auth::id()])`, lalu `Auth::login($target)`. Audit `Log::info`.
- `kembali(): void` — ambil `impersonator_id`; bila ada user superadmin asli → `Auth::login`
  user itu; hapus `session('impersonator_id')`. Audit `Log::info`. Idempoten/aman bila key hilang.
- `sedangImpersonate(): bool` — `session()->has('impersonator_id')`.
- `impersonator(): ?User` — user superadmin asli (untuk banner), null bila tidak impersonate.

### 2. Controller — `ImpersonationController`

- `mulai(string $role)` (POST): hanya saat `Auth::user()->isSuperadmin()` && `!sedangImpersonate()`
  (cegah berlapis). Try service → redirect ke `route(targetUser->homeRoute())` dengan flash
  sukses; catch (wakil kosong / role invalid) → redirect back dengan flash error.
- `kembali()` (POST): hanya saat `sedangImpersonate()`. Service::kembali → redirect ke
  `route('superadmin.dashboard')` (atau homeRoute superadmin asli) dengan flash info.

### 3. Routes (`routes/web.php`)

```
Route::prefix('impersonate')->name('impersonate.')->group(function () {
    // leave: HANYA 'auth' (tanpa 'verified') agar superadmin selalu bisa keluar
    // walau user target belum terverifikasi emailnya.
    Route::middleware('auth')
        ->post('/leave', [ImpersonationController::class, 'kembali'])->name('leave');

    Route::middleware(['auth', 'verified', 'role:superadmin'])
        ->post('/{role}', [ImpersonationController::class, 'mulai'])->name('start')
        ->where('role', 'admin|pmo|pasien');
});
```

`leave` digate `auth` saja (saat impersonate, current user = target, bukan superadmin) +
pengecekan `sedangImpersonate()` di controller. `start` digate `role:superadmin`.

### 4. UI

- **Dropdown header** ([resources/views/partials/header.blade.php](../../../resources/views/partials/header.blade.php)):
  tambah grup "Lihat sebagai" (3 item form POST ke `impersonate.start` untuk admin/pmo/pasien),
  hanya dirender saat `auth()->user()->isSuperadmin()` (otomatis hilang saat impersonate karena
  current user bukan superadmin lagi).
- **Banner** di [resources/views/layouts/app.blade.php](../../../resources/views/layouts/app.blade.php):
  saat `session()->has('impersonator_id')`, tampilkan banner sticky di atas konten:
  "⚠ Anda melihat sebagai **{nama}** ({label role}). [Kembali ke Super Admin]" (form POST ke
  `impersonate.leave`). Warna mencolok (mis. `bg-warning`).

### 5. Keamanan & edge case

- Mulai impersonate: hanya superadmin asli & belum impersonate. Role target dibatasi regex
  `admin|pmo|pasien` di route + divalidasi ulang di service.
- Tidak boleh impersonate role `superadmin`.
- `is_active`: wakil yang dipilih pasti aktif; `EnsureUserHasRole` tetap memeriksa is_active.
- Wakil kosong → flash error, tidak mengubah sesi.
- `kembali()` saat `impersonator_id` hilang/expired → fallback aman (tetap di sesi sekarang
  atau arahkan ke login) tanpa error fatal.
- Tidak ada nesting: route `start` ditolak saat sedang impersonate (controller cek).

## Komponen & batasan

| Unit | Tanggung jawab | Bergantung pada |
|---|---|---|
| `ImpersonationService` | mulai/kembali, pilih wakil, state session, audit | model User, Auth, session |
| `ImpersonationController` | gerbang HTTP + redirect/flash | service |
| header (UI) | tombol "Lihat sebagai" (superadmin) | service/Auth |
| layout banner (UI) | indikator + tombol kembali | session/service |
| routes | start (superadmin) + leave (auth) | controller |

## Pengujian

- Superadmin `mulai('pasien')` → `Auth::id()` = user pasien wakil; `session('impersonator_id')`
  = id superadmin; redirect ke `pasien.dashboard`.
- Non-superadmin POST `start` → 403 (middleware `role:superadmin`).
- `mulai('superadmin')` ditolak (route regex tidak cocok → 404; uji juga guard service bila dipanggil langsung).
- Wakil kosong (tidak ada user aktif role itu) → redirect back + flash error, sesi tak berubah.
- `kembali()` → `Auth::id()` kembali ke superadmin, `session('impersonator_id')` hilang,
  redirect ke `superadmin.dashboard`.
- Multi-superadmin: superadmin kedua juga bisa `mulai`/`kembali` (gate by isSuperadmin, bukan id).

## Di luar lingkup (YAGNI)

- Memilih user spesifik (saat ini hanya wakil otomatis; mudah diupgrade nanti).
- Mode read-only (diputuskan mode penuh).
- Impersonate berantai / impersonate superadmin lain.
- Halaman daftar/log impersonation (cukup `Log::info`).
