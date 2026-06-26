# Desain: Dashboard Data Nyata (mengganti data dummy)

**Tanggal:** 2026-06-26
**Status:** Disetujui (menunggu review spec)
**Lingkup:** Mengganti seluruh data dummy di 4 dashboard (Pasien, PMO, Admin, Superadmin) dengan query nyata. Pendekatan "esensial dulu": kartu statistik & daftar inti memakai data nyata; widget historis (grafik, tracker, timeline) tetap dirender namun dengan empty-state bila data belum ada.

## Latar belakang & masalah

Keempat dashboard saat ini dirender via `Route::view()` langsung tanpa controller, dan seluruh angka/daftar/grafik di-hardcode di blok `@php`:

- `resources/views/dashboard/pasien.blade.php` (baris ~476–571 + chart JS ~871)
- `resources/views/dashboard/pmo.blade.php` (baris ~498–633)
- `resources/views/dashboard/superadmin.blade.php` (baris ~167–172 + chart JS ~365)
- `resources/views/dashboard/admin.blade.php`

Route terkait di `routes/web.php`: 241 (pasien), 261 (pmo), 596 (superadmin), 607 (admin).

## Pendekatan terpilih (A): Controller + DashboardService

Sesuai konvensi codebase (Controller → Service → Repository → Model; Service statis):

1. Ganti 4 `Route::view()` menjadi `DashboardController@{pasien,pmo,admin,superadmin}` (atau 1 controller dengan 4 method).
2. Buat `App\Services\DashboardService` dengan method statis per-role yang mengembalikan satu array view-model (semua angka, daftar, dan data grafik dalam bentuk array agregat).
3. Query berat dikumpulkan di `App\Repos\DashboardRepository` (statis, sesuai pola repo lain). Service memetakan hasil repo ke view-model + menghitung turunan (kepatuhan %, streak).
4. Data grafik dikirim sebagai array ke view; Chart.js membaca JSON yang dirender server (`@json(...)`). Belum ada endpoint AJAX terpisah pada tahap ini.
5. Blade: ganti blok `@php` dummy dengan variabel dari controller; tambahkan empty-state pada widget historis.

Alasan: selaras pola berlapis yang sudah ada, dapat dites, dan tidak menaruh logika di view. Tidak dipilih: View Composer (di luar konvensi, sulit dites) dan query inline di blade (melanggar arsitektur).

## Sumber data (model & scope yang sudah ada)

- `PengingatKejadian` (`pengingat_kejadian`): slot MO termaterialisasi per hari. Status `menunggu` / `dikonfirmasi` / `terlewat`. Field: `jenis`, `user_pasien_id`, `user_pmo_id`, `id_pasien_pmo`, `waktu_jadwal`, `status`. **Sumber kebenaran "hari ini" untuk MO.**
- `PengingatMoLog` (`pengingat_mo_logs`): log konfirmasi MO. `patuh_menit`, accessor `patuh_kategori` (≤15 `tepat_waktu`, ≤60 `terlambat`, >60 `sangat_terlambat`), scope `today/betweenDates/forUser`.
- `PengingatCgdLog` (`pengingat_cgd_logs`): `hasil_mgdl`, `kategori_hasil` (`normal/tidak_terkontrol/tinggi/berbahaya`), `tgl_cgd`, scope `today/betweenDates/kategori/forUser`.
- `JadwalCgd` + `JadwalCgdPeserta`: jadwal CGD = event bersama; peserta per pasien-PMO. Sumber "CGD hari ini".
- `PasienPmo` (`pasien_pmos`): pemetaan pasien↔PMO. Scope `forPmo/forPasien/active`. Dipakai oleh mesin pengingat (bukan `PasienProfile`).
- `Pengumuman`, `Edukasi`: widget konten.
- `MasterObat`, `User`: hitungan agregat admin/superadmin.

Catatan: gunakan `PasienPmo` (bukan `PasienProfile`) untuk relasi pasien–PMO agar konsisten dengan mesin pengingat. Blade pasien lama memakai `pasienProfile?->pmo` — diganti ke `PasienPmo`.

## Definisi metrik (disepakati, dapat disesuaikan)

- **Kepatuhan (%)**: dari log MO 30 hari terakhir milik pasien, persentase yang `patuh_kategori = tepat_waktu` (selisih ≤15 menit). `0` bila belum ada log.
- **Streak**: jumlah hari berturut-turut sampai hari ini tanpa `PengingatKejadian` berstatus `terlewat` (hari tanpa jadwal dilewati/tidak memutus). `0` bila tidak ada riwayat.
- **Perlu perhatian (PMO)**: pasien binaan yang punya `terlewat` hari ini ATAU log CGD `berbahaya`/`tinggi` dalam 7 hari terakhir.
- **Perlu tindak lanjut (Admin)**: jumlah `PengingatKejadian` `terlewat` (dan/atau `menunggu` lewat waktu) hari ini.

## Rincian per dashboard

### Pasien — `DashboardService::untukPasien(User $pasien)`
- Kartu: `obat_hari_ini`/`obat_selesai` (kejadian MO hari ini: total & `dikonfirmasi`), `cgd_hari_ini`/`cgd_selesai` (peserta CGD hari ini & `PengingatCgdLog` hari ini), `kepatuhan`, `streak`.
- `jadwal_hari_ini[]`: gabungan slot MO (dari kejadian) + CGD hari ini, urut jam, status `done|upcoming|missed`.
- `week_tracker[]` (7 hari): per hari `obat` (dikonfirmasi/total) & `gd` (jumlah cek/target). Empty-state bila kosong.
- `gd_trend` (untuk chart): ≤14 hasil CGD terakhir (`tgl`, `hasil_mgdl`). Array kosong → empty-state.
- `pmo`: dari `PasienPmo` (nama, jenis, no. WA PMO dari biodata user PMO).
- `pengumuman[]`: Pengumuman terbit terbaru (limit kecil). `tips[]`: Edukasi terbaru; fallback teks statis bila kosong.

### PMO — `DashboardService::untukPmo(User $pmo)`
- Pasien binaan: `PasienPmo::forPmo($pmo->id)->active()`.
- Kartu: `total_pasien`, `patuh_hari_ini` (kejadian dikonfirmasi hari ini lintas binaan), `perlu_perhatian`, `total_jadwal_hari_ini`.
- `daftar_pasien[]`: tiap pasien + kepatuhan % (30 hari) + hasil GD terakhir.
- `timeline[]`: log MO+CGD terbaru lintas binaan (limit). Empty-state.
- `tips[]`: statis.

### Admin & Superadmin — `DashboardService::untukAdmin(string $role)`
- Kartu: `total_pasien` (`PasienPmo` distinct / users role pasien), `total_pmo`, `total_obat` (`MasterObat`), `perlu_tindak_lanjut`.
- `tren_30hari` (chart): jumlah log CGD per hari selama 30 hari. `distribusi_kategori` (chart): hitungan `kategori_hasil`. Empty-state bila nol.
- `aktivitas_terbaru[]` / `pasien_teratas[]`.
- Superadmin: tambah ringkasan jumlah user per role.

## Arsitektur file

- `app/Http/Controllers/DashboardController.php` — 4 method tipis, kembalikan view + view-model.
- `app/Services/DashboardService.php` — `untukPasien/untukPmo/untukAdmin`, hitung kepatuhan & streak.
- `app/Repos/DashboardRepository.php` — query agregat (statis, `DB`/Eloquent).
- Blade: ganti blok `@php` dummy, tambah komponen/partial empty-state ringan.

## Error handling & edge cases

- DB kosong saat testing → semua hitungan `0`, semua daftar `[]`, grafik tampil empty-state. Tidak ada error.
- Pasien tanpa PMO / PMO tanpa binaan → kartu relasi tampil placeholder "Belum ada".
- Pembagian kepatuhan saat penyebut 0 → kembalikan `0`, hindari division-by-zero.
- Streak tanpa riwayat → `0`.

## Rencana pengujian

- Unit/Feature `DashboardServiceTest`: kepatuhan dihitung benar (mis. 3 dari 4 tepat → 75%); penyebut 0 → 0%; streak terputus oleh `terlewat`; empty-state mengembalikan 0/`[]`.
- Feature: tiap route dashboard memuat (200) untuk role yang sesuai dan menolak role lain.
- Seed minimal di test (factory) untuk pasien dengan beberapa log MO/CGD.

## Di luar lingkup (tahap berikutnya)

- Endpoint AJAX/real-time untuk grafik (saat ini dirender server).
- Halaman placeholder pasien/admin/transaksi (riwayat, master-pasien, master-pmo, dll).
- Pembersihan seeder uji untuk produksi.
