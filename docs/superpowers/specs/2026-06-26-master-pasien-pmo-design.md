# Desain: Master Pasien & Master PMO (Direktori + Detail)

**Tanggal:** 2026-06-26
**Status:** Disetujui (menunggu review spec)
**Lingkup:** Mengganti 2 halaman placeholder admin (`/admin/master/pasien`, `/admin/master/pmo`) dengan direktori + halaman detail read-focused. TANPA CRUD baru — pengelolaan akun tetap di modul `master-user`, pemetaan di `pasien-pmo`.

## Latar belakang

`routes/web.php` (baris 607–610) di grup admin (`role:admin,superadmin`, `prefix('admin')`, `name('admin.')`):
```php
Route::view('/master/pasien', 'placeholder')->name('master.pasien')->defaults('meta', ['title' => 'Master Pasien']);
Route::view('/master/pmo', 'placeholder')->name('master.pmo')->defaults('meta', ['title' => 'Master PMO']);
```
Permission `master-pasien.{index,show,create,edit,delete}` & `master-pmo.*` SUDAH ada di `RolePermissionSeeder` (diberikan ke admin & superadmin). Hanya `index` & `show` yang dipakai modul read-focused ini.

Modul terkait yang sudah ada (jangan diduplikasi): `master-user` (akun + biodata), `pasien-pmo` (pemetaan pasien↔PMO).

## Pendekatan terpilih (A): Controller tipis + MasterDirektoriService

Server-rendered + pagination + kotak cari (konsisten dengan halaman pasien yang baru dibuat). **Pakai ulang** `DashboardRepository` (kepatuhan) & `PasienRiwayatService` (jadwal/riwayat) → minim kode baru.

- `app/Http/Controllers/MasterPasienController.php` — `index(Request)`, `show(string $id)`.
- `app/Http/Controllers/MasterPmoController.php` — `index(Request)`, `show(string $id)`.
- `app/Services/MasterDirektoriService.php` — statis: `daftarPasien`, `detailPasien`, `daftarPmo`, `detailPmo`.
- Blade: `master-pasien/index.blade.php`, `master-pasien/show.blade.php`, `master-pmo/index.blade.php`, `master-pmo/show.blade.php`.
- `routes/web.php` — ganti 2 `Route::view` jadi action + tambah route `show`, gated permission.
- `resources/views/components/sidebar.blade.php` — tambah item "Master Pasien" & "Master PMO" di grup Master Data (`@can`).

Tidak dipilih: B (DataTables/AJAX — kolom kepatuhan agregat mahal di server-side, lebih banyak kode untuk read-only), C (tanpa detail — ditolak).

## Sumber data (pakai ulang yang sudah ada)

- **Roster pasien**: `PasienPmo` (scope `active`) — sudah berisi `nama_pasien`, `nik`, `status_diabetes`, `nama_pmo`, `id_user`, `pmo_user_id`, `is_active`. Satu baris = satu registrasi pasien.
- **Kepatuhan**: `DashboardRepository::hitungKepatuhanMo($idUser)` (% 30 hari). Jumlah CGD: `PengingatCgdLog::forUser($idUser)->count()`.
- **Jadwal aktif**: MO via `JadwalMinumObat` (scope `active`) untuk pasien; CGD mendatang via `PasienRiwayatService::jadwalCgdPasien($idUser)['mendatang']`.
- **Riwayat terbaru**: `PengingatMoLog::forUser($idUser)` & `PengingatCgdLog::forUser($idUser)` (limit kecil, terbaru).
- **Roster PMO**: `User` dengan `role = 'pmo'`. Jumlah binaan: `PasienPmo::forPmo($pmoId)->active()->count()`.
- **Biodata**: `User->biodata` (`UserBiodata`: `jenis_kelamin`/`jenis_kelamin_label`, `tanggal_lahir`, `alamat_lengkap`); kontak via `users.whatsapp_number`.

## Rincian halaman

### 1. Master Pasien — Direktori — `GET /admin/master/pasien` (`admin.master.pasien`, permission `master-pasien.index`)
`index(Request)` → `MasterDirektoriService::daftarPasien(['cari' => ...])` → paginator atas `PasienPmo::active()` (eager `pasien`, `pmo`), difilter cari (nama/NIK via scope `search` yang sudah ada). Tiap baris dipetakan: `id_user`, `nama`, `nik`, `status_diabetes`, `nama_pmo`, `kepatuhan` (%), `is_active`. Blade: tabel paginated + form cari (GET, `withQueryString`), kolom Nama, NIK, Status Diabetes, PMO, Kepatuhan, Status, aksi **Detail** (`admin.master.pasien.show`). Empty-state Indonesian.

### 2. Master Pasien — Detail — `GET /admin/master/pasien/{id}` (`admin.master.pasien.show`, permission `master-pasien.show`, `{id}` = UUID user, `->where('id','[0-9a-f\-]+')`)
`show($id)` → `MasterDirektoriService::detailPasien($id)`:
- **Profil & PMO**: nama, NIK, jenis kelamin, tanggal lahir, alamat, kontak (whatsapp), status diabetes; PMO pendamping (nama + kontak) dari `PasienPmo`.
- **Ringkasan kepatuhan**: `kepatuhan` (%), `jumlah_cgd`.
- **Jadwal aktif**: `mo[]` (obat, jam, frekuensi), `cgd[]` (tanggal, jam, tempat) — mendatang.
- **Riwayat terbaru**: `mo[]` & `cgd[]` (≤5 terakhir) dengan badge.
- Tombol "Kelola akun" → `master-user.edit` (atau `.show`) bila ada permission; jika `$id` tak punya `PasienPmo`/bukan pasien → `abort(404)`.

### 3. Master PMO — Direktori — `GET /admin/master/pmo` (`admin.master.pmo`, permission `master-pmo.index`)
`index(Request)` → `MasterDirektoriService::daftarPmo(['cari' => ...])` → paginator atas `User::where('role','pmo')` (cari nama/username/email), tiap baris: `id`, `nama`, `kontak` (whatsapp), `jumlah_binaan`, `is_active`. Blade: tabel + cari, aksi **Detail** (`admin.master.pmo.show`). Empty-state.

### 4. Master PMO — Detail — `GET /admin/master/pmo/{id}` (`admin.master.pmo.show`, permission `master-pmo.show`)
`show($id)` → `MasterDirektoriService::detailPmo($id)`:
- **Profil PMO**: nama, kontak, status aktif; jika `$id` bukan user role pmo → `abort(404)`.
- **Daftar pasien binaan**: tiap binaan `['nama','status_diabetes','kepatuhan','gd_terakhir']` (pakai ulang `DashboardRepository::hitungKepatuhanMo` + `hasilGdTerakhir`). Empty-state bila belum ada binaan.

### 5. Sidebar & route
- Sidebar: di grup "Master Data" (`sidebar.blade.php` ~111), tambah `@can('master-pasien.index')` item "Master Pasien" (`admin.master.pasien`) & `@can('master-pmo.index')` item "Master PMO" (`admin.master.pmo`), penanda aktif via `request()->routeIs('admin.master.pasien*')` dst, ikon RemixIcon.
- Route: di grup admin, ganti 2 `Route::view` dan tambah route `show`, masing-masing `->middleware('permission:...')`.

## Otorisasi & keamanan

- Semua route di grup `['auth','verified','role:admin,superadmin']` yang sudah ada, plus `permission:master-pasien.index/.show` & `master-pmo.index/.show` per aksi.
- Detail menerima `{id}` dari URL (memang admin boleh melihat pasien/PMO mana pun) — tetapi divalidasi: `detailPasien` memastikan id adalah pasien (punya `PasienPmo`), `detailPmo` memastikan role pmo; selain itu `abort(404)`.

## Error handling & edge cases

- Cari kosong → tampil semua (paginated).
- Pasien tanpa log → kepatuhan 0, riwayat/jadwal empty-state.
- PMO tanpa binaan → daftar binaan empty-state, `jumlah_binaan` 0.
- `{id}` tak valid / bukan pasien|pmo → 404.
- Pasien tanpa biodata → field biodata tampil "-".

## Rencana pengujian

- Feature `MasterPasienDirektoriTest`: admin buka `/admin/master/pasien` → 200, melihat nama pasien; cari memfilter; non-admin / tanpa permission → 403.
- Feature `MasterPasienDetailTest`: admin buka detail pasien → 200, melihat profil + kepatuhan + jadwal/riwayat; id non-pasien → 404.
- Feature `MasterPmoDirektoriTest` & `MasterPmoDetailTest`: serupa untuk PMO; detail menampilkan daftar binaan; id non-pmo → 404.
- Unit/Feature `MasterDirektoriServiceTest`: `daftarPasien` memetakan kepatuhan benar & menghormati cari; `detailPmo` menghitung binaan; isolasi tidak relevan (admin) tetapi struktur array diuji.

## Di luar lingkup (roadmap berikutnya)

- Create/edit/delete pasien & PMO (tetap via `master-user` + `pasien-pmo`).
- Halaman transaksi admin (`transaksi/jadwal-cgd`, `pillbox-mo`, `alat-cgd`).
- Ekspor/cetak direktori.
