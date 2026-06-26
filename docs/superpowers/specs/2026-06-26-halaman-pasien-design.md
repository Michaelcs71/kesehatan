# Desain: Halaman Pasien (Jadwal CGD, Riwayat, Konfirmasi Pending)

**Tanggal:** 2026-06-26
**Status:** Disetujui (menunggu review spec)
**Lingkup:** Mengganti 4 halaman placeholder area pasien dengan halaman fungsional, dikonsolidasi menjadi 2 halaman nyata + redirect, plus menu sidebar pasien. Read-only, kecuali konfirmasi pengingat MO yang memakai alur konfirmasi yang sudah ada.

## Latar belakang

Area pasien (`routes/web.php` baris 244–251) berisi 4 route `Route::view('placeholder')`:
- `pasien.jadwal.cgd` (`/pasien/jadwal-cgd`)
- `pasien.pengingat.mo` (`/pasien/pengingat-mo`)
- `pasien.pengingat.cgd` (`/pasien/pengingat-cgd`)
- `pasien.riwayat` (`/pasien/riwayat`)

Selain itu, `resources/views/components/sidebar.blade.php` tidak punya menu khusus pasien — pasien hanya melihat **Dashboard** dan **Profil Saya** (menu Transaksi di-gate permission admin/PMO). Jadi keempat halaman ini bahkan belum tertaut.

Halaman `pengingat-mo`, `pengingat-cgd`, dan `riwayat` semuanya menampilkan riwayat log pasien (tumpang tindih). Keputusan desain: **konsolidasi** menjadi satu halaman Riwayat bertab, dan arahkan route pengingat-mo/cgd ke Riwayat dengan tab terpilih (semua route lama tetap hidup).

## Pendekatan terpilih (A): PasienController + PasienRiwayatService

Sesuai konvensi Controller → Service (Service statis). Halaman read-only ramah-mobile, server-rendered dengan pagination Laravel (bukan DataTables/AJAX — lebih sederhana untuk tampilan pasien).

- `app/Http/Controllers/PasienController.php` — method tipis: `jadwalCgd()`, `riwayat(Request)`, `pengingatMo()`, `pengingatCgd()` (dua terakhir hanya `redirect()->route('pasien.riwayat', ['tab' => ...])`).
- `app/Services/PasienRiwayatService.php` — statis: `jadwalCgdPasien(string $userId): array`, `riwayatMo(string $userId, array $filter): LengthAwarePaginator`, `riwayatCgd(string $userId, array $filter): LengthAwarePaginator`, `pendingKonfirmasi(string $userId): Collection`.
- Blade: `resources/views/pasien/jadwal-cgd.blade.php`, `resources/views/pasien/riwayat.blade.php` (extends `layouts.app`).
- `routes/web.php` — ganti 4 `Route::view` jadi action controller.
- `resources/views/components/sidebar.blade.php` — tambah seksi menu untuk `$isPasien`.

Tidak dipilih: B (membuka permission admin untuk pasien — berantakan), C (query inline di blade — melanggar arsitektur).

## Sumber data (model & relasi yang sudah ada)

- **Jadwal CGD pasien**: `JadwalCgdPeserta::whereHas('pasienPmo', fn ($q) => $q->where('id_user', $userId))->with('jadwalCgd')`. `JadwalCgd` punya `tgl_jadwal_cgd`, `jam_mulai`, `tempat`, `puasa`, `status`. Pisah jadi **mendatang** (`tgl_jadwal_cgd >= today`) & **lewat**.
- **Riwayat MO**: `PengingatMoLog::forUser($userId)` (+ scope `betweenDates`). Accessor: `patuh_kategori`, `patuh_badge_color`, `patuh_label`, `jam_minum_obat_format`; field `nama_obat`, `tgl_minum_obat`, `foto_url`.
- **Riwayat CGD**: `PengingatCgdLog::forUser($userId)`. Accessor: `kategori_label`, `kategori_color`, `kategori_icon`, `jam_cgd_format`; field `hasil_mgdl`, `tgl_cgd`, `tempat_cgd`, `foto_url`.
- **Konfirmasi pending (MO saja)**: `PengingatKejadian::where('user_pasien_id', $userId)->where('jenis', 'mo')->menunggu()->with(...)->orderBy('waktu_jadwal')`. Tautan ke route `pengingat.konfirmasi.show` (param `kejadian` = id). CGD tidak punya alur konfirmasi → tidak ada konfirmasi pending CGD.

## Rincian halaman

### 1. Jadwal CGD pasien — `GET /pasien/jadwal-cgd` (`pasien.jadwal.cgd`)
Controller `jadwalCgd()` → `PasienRiwayatService::jadwalCgdPasien($userId)` mengembalikan `['mendatang' => [...], 'lewat' => [...]]`. Tiap item: `tanggal`, `jam`, `tempat`, `puasa` (label "Wajib"/"Tidak perlu"), `status`. Blade: dua kartu/seksi (Mendatang, Selesai/Lewat), masing-masing `@forelse ... @empty` dengan empty-state Indonesian. Read-only.

### 2. Riwayat — `GET /pasien/riwayat` (`pasien.riwayat`)
Query param `tab` ∈ {`obat`, `gula`} (default `obat`). Controller `riwayat(Request)`:
- `pendingKonfirmasi($userId)` → banner di atas: tiap pengingat MO `menunggu` menampilkan nama obat + jam target + tombol "Konfirmasi" → `route('pengingat.konfirmasi.show', $kejadian->id)`. Sembunyikan banner bila kosong.
- Tab **Minum Obat** (`obat`): `riwayatMo($userId, $filter)` paginator. Kolom: tanggal, jam minum, nama obat, badge ketepatan (`patuh_badge_color`/`patuh_label`). Filter tanggal opsional (`dari`/`sampai`).
- Tab **Cek Gula Darah** (`gula`): `riwayatCgd($userId, $filter)` paginator. Kolom: tanggal, jam, hasil mg/dL, badge kategori (`kategori_color`/`kategori_label`), tempat.
- Pagination Laravel mempertahankan query string (`->withQueryString()`), tab dipertahankan via link.
- Empty-state per tab.

### 3. Redirect halaman pengingat
- `GET /pasien/pengingat-mo` (`pasien.pengingat.mo`) → `redirect()->route('pasien.riwayat', ['tab' => 'obat'])`.
- `GET /pasien/pengingat-cgd` (`pasien.pengingat.cgd`) → `redirect()->route('pasien.riwayat', ['tab' => 'gula'])`.

### 4. Sidebar pasien
Di `sidebar.blade.php`, tambah blok `@if($isPasien)` dengan nav-title "Menu Saya" dan tautan: **Jadwal CGD** (`pasien.jadwal.cgd`), **Riwayat** (`pasien.riwayat`), dengan penanda `active` via `request()->routeIs(...)`. Dashboard & Profil sudah ada. Ikon RemixIcon konsisten dengan menu lain.

## Otorisasi & keamanan

- Semua route tetap di grup `['auth','verified','role:pasien']` (sudah ada). Pasien hanya bisa mengakses area pasien.
- Semua query difilter `id_user`/`user_pasien_id` = pasien yang login → pasien **tidak** dapat melihat data pasien lain. Service menerima `Auth::id()` dari controller; tidak menerima id dari request.
- Konfirmasi memakai `KonfirmasiPengingatController` yang sudah meng-otorisasi (`pastikanBerhak` cek `user_pasien_id`/`user_pmo_id`).

## Error handling & edge cases

- Pasien tanpa jadwal/log → seksi/tab tampil empty-state, tanpa error.
- Tidak ada pengingat pending → banner konfirmasi tidak dirender.
- `tab` tak dikenal → fallback ke `obat`.
- Filter tanggal kosong/invalid → diabaikan (tampilkan semua), divalidasi via FormRequest sederhana atau `nullable|date`.
- Pasien tanpa PasienPmo → jadwal CGD kosong (empty-state).

## Rencana pengujian

- Feature `PasienJadwalCgdTest`: pasien membuka `/pasien/jadwal-cgd` → 200; melihat event CGD miliknya; **tidak** melihat event pasien lain; non-pasien → 403.
- Feature `PasienRiwayatTest`: `/pasien/riwayat` → 200; tab obat menampilkan log MO pasien; tab gula menampilkan log CGD pasien; data pasien lain tak muncul; banner pending muncul saat ada kejadian `menunggu` dan tautannya benar; non-pasien → 403.
- Feature `PasienPengingatRedirectTest`: `/pasien/pengingat-mo` → redirect `pasien.riwayat?tab=obat`; `/pasien/pengingat-cgd` → redirect `?tab=gula`.
- Unit/Feature `PasienRiwayatServiceTest`: `jadwalCgdPasien` memisah mendatang/lewat dengan benar; `riwayatMo`/`riwayatCgd` hanya mengembalikan data pasien tsb; `pendingKonfirmasi` hanya kejadian MO `menunggu` milik pasien.

## Di luar lingkup (tahap berikutnya)

- Konfirmasi atau input hasil CGD oleh pasien (belum ada alurnya di sistem).
- Input manual log MO oleh pasien (konfirmasi tetap via alur kejadian yang ada).
- Ekspor/cetak riwayat.
- Halaman admin master-pasien/master-pmo & transaksi (tahap roadmap berikutnya).
