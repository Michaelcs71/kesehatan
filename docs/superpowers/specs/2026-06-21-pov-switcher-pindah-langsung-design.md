# Desain: POV Switcher — pindah role langsung saat ber-POV

Tanggal: 2026-06-21
Status: disetujui (siap rencana implementasi)

## Latar belakang

Fitur POV switcher (impersonation) sudah ada (lihat
`2026-06-21-pov-switcher-impersonation-design.md`). Saat ini: tombol "Lihat sebagai"
hanya muncul untuk superadmin dan hilang begitu ber-POV; satu-satunya kontrol saat POV
adalah banner "Kembali ke Super Admin". User ingin: **saat ber-POV, opsi ganti role tetap
ada (sama seperti saat superadmin) dan klik role lain langsung pindah POV** tanpa harus
kembali ke superadmin dulu.

## Keputusan desain (dikonfirmasi user)

1. Selama ber-POV, grup "Lihat sebagai" tetap muncul di dropdown header (lokasi sama).
2. Klik role lain saat ber-POV → **langsung pindah** POV ke role itu (tanpa langkah kembali).
3. Banner "Kembali ke Super Admin" tetap ada untuk keluar penuh.
4. Opsi role yang **sedang aktif** tetap ditampilkan, diberi tanda "aktif" (klik = re-impersonate
   wakil sama, tidak diblokir).

## Prinsip kunci: otorisasi berbasis "operator"

Otorisasi tidak lagi berdasar current user (saat POV ia bukan superadmin), melainkan
**operator** = superadmin asli:

- Bila sedang impersonate → operator = `ImpersonationService::impersonator()` (user dari
  `impersonator_id` di session).
- Bila tidak → operator = `Auth::user()`.

Boleh mulai/pindah POV hanya bila operator ada **dan** `isSuperadmin()`. `impersonator_id`
**selalu** diset ke id operator (superadmin), sehingga pindah pasien→admin tetap menyimpan
superadmin sebagai titik kembali — kunci tak pernah menunjuk user yang ditiru, nesting aman
secara konstruksi. Session ditandatangani server (tak bisa dipalsukan klien).

## Perubahan komponen

### Service `ImpersonationService`

- Tambah `mulaiSebagai(User $operator, string $roleValue): User`:
  - validasi `$roleValue ∈ {admin,pmo,pasien}` (else `\InvalidArgumentException`);
  - cari wakil `where(role)->where(is_active,true)->orderBy(created_at)->first()` (else `\RuntimeException`);
  - `session([SESSION_KEY => $operator->id])`; `Auth::login($target)`; `Log::info`; return target.
  - Dipakai untuk **start dan switch** (tidak ada guard "sudah impersonate" — pindah diizinkan).
- `kembali(): bool`, `sedangImpersonate(): bool`, `impersonator(): ?User` tetap.
- Method lama `mulai(string $roleValue): User` (beserta guard `\LogicException` "sudah
  impersonate") **dihapus**; controller memanggil `mulaiSebagai($operator, $role)` langsung.
  Semua pemanggil lama (termasuk test) disesuaikan ke `mulaiSebagai`.

### Controller `ImpersonationController`

- `mulai(string $role)`:
  - `$operator = ImpersonationService::sedangImpersonate() ? ImpersonationService::impersonator() : auth()->user();`
  - bila `! $operator || ! $operator->isSuperadmin()` → `abort(403)`.
  - try `ImpersonationService::mulaiSebagai($operator, $role)`; catch → `back()->with('error', ...)`.
  - sukses → redirect `route($target->homeRoute())` + flash sukses.
- `kembali()` tetap (restore superadmin; bila gagal → logout + redirect login).

### Routes (`routes/web.php`)

- `impersonate.start` POST `/{role}` (`where('role','admin|pmo|pasien')`): middleware **`auth`
  saja** (lepas `role:superadmin` dan `verified`; otorisasi via operator di controller).
- `impersonate.leave` POST `/leave` (`auth`) tetap.

Keamanan: pasien biasa (tidak ber-POV) yang POST ke `start` → operator = dirinya → bukan
superadmin → 403. Hanya operator superadmin (langsung, atau via impersonation yang ia mulai)
yang lolos.

### UI

- [resources/views/partials/header.blade.php](../../../resources/views/partials/header.blade.php):
  grup "Lihat sebagai" tampil saat `auth()->user()->isSuperadmin() || \App\Services\ImpersonationService::sedangImpersonate()`.
  Selama POV, role yang sedang aktif (`auth()->user()->role?->value === $rv`) diberi badge
  "aktif" tapi tetap bisa diklik.
- Banner di [resources/views/layouts/app.blade.php](../../../resources/views/layouts/app.blade.php)
  tetap (Mode POV + Kembali).

## Pengujian

- **Diperbarui:** test "service tolak mulai saat sudah impersonate" **dihapus** (pindah kini sah);
  test "service menolak role invalid" disesuaikan memanggil `mulaiSebagai($superadmin, 'superadmin')`
  dan tetap mengharapkan `\InvalidArgumentException`.
- **Baru:** superadmin ber-POV sebagai pasien lalu POST `/impersonate/admin` → current user =
  admin wakil; `session(SESSION_KEY)` **tetap** id superadmin (bukan id pasien); redirect `admin.dashboard`.
- **Baru:** pasien biasa (tanpa ber-POV) POST `/impersonate/admin` → 403; sesi tak berubah.
- **Dipertahankan:** start dari superadmin; `leave` memulihkan superadmin; wakil kosong →
  redirect+error; role `superadmin` ditolak (404 via regex); deleted-superadmin → logout aman;
  superadmin kedua juga bisa.

## Di luar lingkup (YAGNI)

- Memilih user spesifik (tetap wakil otomatis).
- Riwayat/log UI impersonation (cukup `Log::info`).
- Mode read-only.
