# Desain: Pengingat Cek Gula Darah (CGD)

Tanggal: 2026-06-21
Status: disetujui (siap rencana implementasi)

## Latar belakang

Mesin pengingat (`pengingat:tick`, tabel `pengingat_kejadian`, Web Push + WhatsApp,
eskalasi pasien→PMO) sudah jalan **hanya untuk Minum Obat (MO)**. CGD ditunda karena
`jadwal_cgds` tidak punya tautan ke pasien dan modelnya event sekali-jalan, bukan
harian-berulang seperti MO.

`jadwal_cgds` adalah **event bersama** (mis. cek gula massal di posyandu): punya
`tgl_jadwal_cgd`, `jam_mulai`/`jam_berakhir`, `tempat`, `puasa` (Wajib/Tidak), `status`.
Tidak ada kolom pasien.

Catatan istilah: modul **pengingat-cgd** (`PengingatCgdLog`) yang sudah ada adalah
*log hasil cek gula* (`hasil_mgdl`, `foto_layar`) — bukan mesin pengingat. Fitur ini
menautkan **jadwal-cgd → peserta → notifikasi**.

## Keputusan desain (dikonfirmasi user)

1. **Relasi CGD↔pasien:** event bersama dengan **tabel peserta** (banyak pasien per jadwal).
2. **Penentuan peserta:** admin memilih manual di form jadwal CGD.
3. **Waktu pengingat:** **2 kali** — (a) saat jadwal dibuat/diaktifkan, (b) H-1 (karena
   sering `puasa = Wajib`). Bukan pada jam event.
4. **Perilaku:** **sekali kirim** ke pasien **dan** PMO. Tanpa pengulangan tiap 10 menit,
   tanpa eskalasi bertahap, tanpa alur konfirmasi.
5. **Tanpa web-push:** kalau pasien tidak punya subscription push → kirim WhatsApp
   (konsisten dengan MO).
6. **Edit jadwal:** peserta yang baru ditambahkan ke jadwal yang notif "dibuat"-nya sudah
   jalan tetap menerima notif "dibuat" (penanda kirimnya masih kosong); peserta lama tidak
   dikirim ulang.

## Arsitektur

Reuse infrastruktur kirim yang ada (`PengingatKirimLog`, `WebPushSender`,
`WhatsAppSender`, `PushSubscription`, cron `pengingat:tick`). **Tidak** memakai tabel
`pengingat_kejadian` (skema-nya ber-orientasi MO + eskalasi). Sebagai gantinya, tabel
pivot peserta merangkap *ledger pengiriman* sehingga pengiriman idempoten dan per-peserta.

### 1. Data — tabel pivot `jadwal_cgd_peserta`

Migration baru:

- `id` uuid primary
- `jadwal_cgd_id` uuid → FK `jadwal_cgds`, cascade on delete
- `id_pasien_pmo` uuid → FK `pasien_pmos`
- `nama_pasien` string — snapshot (pola sama dgn `jadwal_minum_obats`)
- `nama_pmo` string nullable — snapshot
- `dikirim_dibuat_pada` dateTime nullable — penanda fase "dibuat" sudah dikirim
- `dikirim_h1_pada` dateTime nullable — penanda fase "h1" sudah dikirim
- `timestamps`
- unique `(jadwal_cgd_id, id_pasien_pmo)`
- index `(dikirim_dibuat_pada)`, `(dikirim_h1_pada)` untuk scan tick

Model `JadwalCgdPeserta` (HasUuids): `belongsTo JadwalCgd`, `belongsTo PasienPmo`.
`JadwalCgd` dapat relasi `hasMany(JadwalCgdPeserta, 'jadwal_cgd_id')` → `peserta`.

### 2. Pengiriman — lewat cron `pengingat:tick`

`PengingatTickService::jalankan()` ditambah: bila `config('pengingat.aktif.cgd')` true,
panggil `prosesCgd()`. `PengingatTick` command description diperbarui ("MO & CGD").

`PengingatTickService::prosesCgd()` (statis, pola sama dgn service lain):

- Ambil jadwal CGD `status = aktif` dan `tgl_jadwal_cgd >= hari ini`, eager-load `peserta`.
- Untuk tiap peserta:
  - **Fase "dibuat":** jika `dikirim_dibuat_pada` null → dispatch
    `KirimPengingatCgdJob(peserta->id, 'dibuat')`, lalu set `dikirim_dibuat_pada = now`.
    (Karena hanya memproses jadwal **aktif**, ini otomatis mencakup "saat dibuat" dan
    "saat diaktifkan"; terkirim ≤1 menit setelah simpan.)
  - **Fase "h1":** hitung `waktuKirimH1 = (tgl_jadwal_cgd − 1 hari) jam config`.
    Jika `now >= waktuKirimH1` dan `dikirim_h1_pada` null → dispatch
    `KirimPengingatCgdJob(peserta->id, 'h1')`, lalu set `dikirim_h1_pada = now`.
    (`>=` agar tick yang terlewat sedikit tetap mengirim; stamp menjaga sekali-kirim.)

Penandaan (`dikirim_*_pada`) dilakukan **sebelum/atas keberhasilan dispatch** untuk
mencegah dobel-kirim antar tick; kegagalan kirim aktual ditangani retry job + dicatat di
`PengingatKirimLog` (pola sama dgn MO).

### 3. Job `KirimPengingatCgdJob`

`__construct(string $pesertaId, string $fase)`; `ShouldQueue`, `tries=3`, backoff sama
dgn MO. `handle()`:

- Muat `JadwalCgdPeserta` (with `jadwalCgd`, `pasienPmo`). Bila tidak ada / jadwal tidak
  aktif → return.
- Resolusi user: `pasien = pasienPmo->id_user`, `pmo = pasienPmo->pmo_user_id`.
- Bangun pesan: tanggal event, `jam_mulai`, `tempat`, status `puasa`, dan label fase
  ("Jadwal baru" vs "Besok"). Contoh isi: *"Cek gula darah 23 Jun 2026 jam 07:00 di
  Posyandu Lebakharjo. Puasa: Wajib."*
- Kirim ke pasien: push bila `PushSubscription` ada, selain itu WA. Kirim ke PMO (bila ada
  `pmo_user_id`): push bila ada, selain itu WA.
- WA pakai template `config('pengingat.whatsapp.cloud_api.template_cgd')`.
- Catat tiap pengiriman ke `PengingatKirimLog` (`kejadian_id` null / disesuaikan; lihat
  catatan implementasi di rencana).

`KirimPengingatJob` (MO) **tidak diubah**.

### 4. Config (`config/pengingat.php`)

- `aktif.cgd => true`
- `cgd => ['jam_h1' => env('PENGINGAT_CGD_JAM_H1', '17:00')]`
- `whatsapp.cloud_api.template_cgd => env('WA_TEMPLATE_CGD', 'pengingat_cgd')`

### 5. UI — form jadwal CGD

- [resources/views/jadwal-cgd/form.blade.php](../../../resources/views/jadwal-cgd/form.blade.php):
  tambah **multi-select peserta** (sumber: daftar `pasien_pmo` aktif, label nama pasien
  + PMO). Pola ikuti select yang sudah ada di form CGD.
- `JadwalCgdService` create/update: simpan jadwal lalu **sync** pivot peserta — buat baris
  baru (snapshot `nama_pasien`/`nama_pmo`, `dikirim_* = null`), hapus peserta yang
  di-uncheck. Peserta lama yang tetap ada: jangan reset penanda kirim.
- `JadwalCgdController` sediakan endpoint options peserta bila belum ada (reuse sumber
  pasien_pmo yang dipakai modul lain).
- `show.blade.php`: tampilkan daftar peserta + status terkirim (dibuat/H-1).
- FormRequest store/update: validasi `peserta` array of uuid pasien_pmo (nullable).

## Komponen & batasan

| Unit | Tanggung jawab | Bergantung pada |
|---|---|---|
| `jadwal_cgd_peserta` (+model) | tautan peserta + ledger kirim | `jadwal_cgds`, `pasien_pmos` |
| `PengingatTickService::prosesCgd()` | pilih peserta jatuh tempo per fase, dispatch, stamp | tabel pivot, config |
| `KirimPengingatCgdJob` | render pesan + kirim push/WA ke pasien & PMO | sender push/WA, `PengingatKirimLog` |
| Form/Service CGD | kelola peserta (sync pivot) | pivot, sumber options pasien_pmo |

## Pengujian

- **Unit `prosesCgd()`:** (a) fase "dibuat" memilih peserta `dikirim_dibuat_pada=null` pada
  jadwal aktif & belum lewat; (b) idempoten — tick kedua tidak dispatch ulang;
  (c) fase "h1" hanya kirim saat `now >= H-1 jam_config`; (d) jadwal nonaktif/sudah lewat
  diabaikan.
- **Feature:** simpan jadwal + peserta → jalankan `pengingat:tick` (Bus fake) → job
  ter-dispatch sekali per fase, untuk pasien & PMO; ledger ter-stamp.
- **Feature form:** store/update men-sync pivot dengan benar (tambah/hapus peserta).

## Di luar lingkup (YAGNI)

- Pengulangan/eskalasi bertahap & alur konfirmasi kehadiran CGD.
- Pendaftaran mandiri oleh pasien.
- Pengingat pada jam event (H-0).
- Perubahan pada `pengingat_kejadian` / `KirimPengingatJob` (MO).
```
