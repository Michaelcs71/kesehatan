# Operasional Pengingat (Minum Obat)

## Wajib jalan di produksi (VPS)
1. Cron Laravel scheduler (sekali saja, di crontab server):
   * * * * * cd /path/app && php artisan schedule:run >> /dev/null 2>&1
2. Queue worker (via supervisor, hidup terus):
   php artisan queue:work --queue=default --tries=3 --backoff=30
3. HTTPS wajib (Service Worker & Web Push hanya jalan di HTTPS / localhost).

## Konfigurasi kunci VAPID (Web Push)
Generate sekali, lalu isi `.env`:
- Di mesin dengan OpenSSL EC (Linux/VPS umumnya OK):
  php artisan tinker --execute="dump(Minishlink\WebPush\VAPID::createVapidKeys());"
- Salin publicKey -> VAPID_PUBLIC_KEY, privateKey -> VAPID_PRIVATE_KEY di `.env`.
- Jalankan: php artisan config:clear
- Catatan: pada sebagian Windows dev, openssl_pkey_new EC bisa gagal ("Unable to create the key").
  Generate di server/WSL/Linux, lalu paste hasilnya ke `.env`.

## Konfigurasi WhatsApp
- Dev/lokal: WA_DRIVER=log (hanya menulis ke log, tanpa biaya).
- Produksi: WA_DRIVER=cloud_api, isi WA_CLOUD_TOKEN & WA_CLOUD_PHONE_ID.
- Submit template "pengingat_obat" (bahasa: id) di Meta Business Manager,
  body 4 parameter: {{1}} nama, {{2}} nama obat, {{3}} jam, {{4}} link konfirmasi.

## Mengatur waktu eskalasi (via .env, tanpa migrasi)
- PENGINGAT_INTERVAL_ULANG   (default 10)  : jeda menit antar pengingat ulang
- PENGINGAT_WA_PASIEN_MENIT  (default 30)  : kirim WA ke pasien setelah X menit belum dikonfirmasi
- PENGINGAT_WA_PMO_MENIT     (default 60)  : libatkan PMO setelah X menit
- PENGINGAT_BATAS_AKHIR_MENIT(default 120) : berhenti & tandai 'terlewat'

## Cara kerja singkat
- `pengingat:tick` (tiap menit): materialisasi slot MO yang jatuh tempo ke tabel
  `pengingat_kejadian`, lalu memproses tiap kejadian 'menunggu' sesuai aturan eskalasi.
- Pasien punya Web Push aktif -> push di menit 0, diulang tiap 10 menit; WA menyusul +30.
- Pasien tanpa push (mis. iOS Safari) -> WA sejak menit 0.
- Belum dikonfirmasi +60 menit -> PMO ikut diingatkan (sekali).
- Konfirmasi via /pengingat/{id}/konfirmasi (upload foto) -> kejadian 'dikonfirmasi',
  pengiriman berhenti otomatis.

## Demo lokal (Windows)
- Jalankan worker: php artisan queue:work
- Picu manual: php artisan pengingat:tick
  (atau jadwalkan `php artisan schedule:run` tiap menit via Task Scheduler Windows)
- WA_DRIVER=log => cek hasil "pengiriman" di storage/logs/laravel.log.

## Tindak lanjut (belum dikerjakan)
- Pengingat CGD: butuh tautan pasien pada jadwal_cgds dulu (lihat spec §2.1).
- iOS push: butuh PWA (manifest + add-to-home-screen); saat ini iOS tercover WA.
- Housekeeping: hapus baris pengingat_kejadian/pengingat_kirim_log lama (>90 hari).
