# Desain: Sistem Pengingat Web Push (utama) + WhatsApp (fallback)

- **Tanggal:** 2026-06-03
- **Modul:** Pengingat Minum Obat (MO) & Cek Gula Darah (CGD)
- **Status:** Disetujui (siap masuk tahap rencana implementasi)

## 1. Latar Belakang & Tujuan

Fitur utama aplikasi adalah **notifikasi pengingat** minum obat (MO) dan cek gula darah
(CGD) untuk pasien diabetes. Karena ini aplikasi web yang diakses dari browser
(mayoritas mobile), notifikasi in-app saja tidak cukup: begitu tab ditutup, notifikasi
in-app mati. Maka pengiriman harus memakai kanal yang sampai walau tab/aplikasi tertutup.

Keputusan kanal:
- **Web Push (utama)** — gratis, sampai walau tab tertutup. Keterbatasan: iOS hanya jalan
  bila aplikasi di-install sebagai PWA.
- **WhatsApp (fallback)** — andal di Indonesia, berbayar. Driver utama **WhatsApp Cloud
  API resmi Meta**, dirancang swappable.

**Catatan sesi (bukan auto-logout):** session dibuat long-lived (tidak auto-logout saat
tab ditutup) supaya tap notifikasi langsung mendarat di halaman konfirmasi tanpa login
ulang. Keamanan mengandalkan kunci layar device, bukan logout agresif (device pribadi).

## 2. Keputusan Desain (hasil diskusi)

| Topik | Keputusan |
|---|---|
| Kanal | Web Push utama + WhatsApp fallback |
| Driver WA | WhatsApp Cloud API resmi Meta (driver swappable via interface) |
| Penerima | Pasien dulu; PMO dieskalasi bila pasien tak kunjung konfirmasi |
| Pemicu WA | Bila belum dikonfirmasi dalam X menit (default 30) |
| Eskalasi | WA pasien +30 mnt, WA PMO +60 mnt (default, semua di config) |
| Pengulangan | Ulang tiap 10 mnt; berhenti saat dikonfirmasi atau lewat batas (120 mnt) |
| iOS / tanpa push | Pasien tanpa push aktif → WA jadi kanal utama sejak menit-0 |
| Hosting | Rekomendasi VPS (cron + queue worker); portabel untuk demo lokal |
| Timezone | `Asia/Jakarta` (acuan perhitungan selisih menit) |

Semua angka menit disimpan di `config/pengingat.php` agar mudah diubah tanpa migrasi.

## 3. Arsitektur: Opsi A — Tabel Kejadian + Scheduler tiap menit

Jadwal hanya menyimpan *aturan* (jam_mulai + frekuensi). Karena pengingat berulang +
eskalasi + perlu tahu status konfirmasi, dibuat tabel **`pengingat_kejadian`** (satu baris
per kemunculan slot). Command `pengingat:tick` jalan tiap menit untuk memateralisasi
kejadian yang jatuh tempo dan memproses kejadian yang masih menunggu.

Mengikuti pola berlapis proyek: **Controller → Service (static) → Repository (static) →
Model**, dengan `DB::transaction` untuk semua tulis.

## 4. Model Data

### 4.1 `push_subscriptions` (langganan Web Push per device)
| kolom | tipe | keterangan |
|---|---|---|
| id | uuid | PK |
| user_id | uuid | FK → users |
| endpoint | text (unique) | URL push service browser |
| public_key | string | kunci `p256dh` |
| auth_token | string | kunci `auth` |
| user_agent | string null | deteksi device / debug |
| timestamps | | |

Satu user boleh punya banyak baris (HP + laptop).

### 4.2 `pengingat_kejadian` (inti Opsi A)
| kolom | tipe | keterangan |
|---|---|---|
| id | uuid | PK |
| jenis | enum `mo`/`cgd` | jenis jadwal |
| jadwal_id | uuid | FK ke jadwal MO / CGD (sesuai `jenis`) |
| id_pasien_pmo | uuid | denormalisasi relasi pasien-pmo |
| user_pasien_id | uuid | penerima utama |
| user_pmo_id | uuid null | penerima eskalasi |
| waktu_jadwal | datetime | tanggal + slot jam (acuan selisih menit) |
| status | enum `menunggu`/`dikonfirmasi`/`terlewat` | |
| konfirmasi_log_id | uuid null | link ke `pengingat_mo_logs`/`pengingat_cgd_logs` |
| dikonfirmasi_pada | datetime null | |
| jumlah_push | int default 0 | penghitung percobaan push |
| jumlah_wa_pasien | int default 0 | penghitung WA pasien |
| jumlah_wa_pmo | int default 0 | penghitung WA PMO |
| terakhir_dikirim_pada | datetime null | menjaga jeda 10 mnt antar-ulang |
| eskalasi_pmo | bool default false | PMO sudah dilibatkan? |
| timestamps | | |

**`unique(jenis, jadwal_id, waktu_jadwal)`** → kunci idempotensi: satu slot = satu kejadian,
walau tick jalan dua kali / telat.

### 4.3 `pengingat_kirim_log` (audit percobaan kirim)
`id`, `kejadian_id` (FK), `kanal` (`push`/`whatsapp`), `target` (`pasien`/`pmo`),
`status` (`terkirim`/`gagal`), `error` (text null), `created_at`. Untuk menelusuri
kegagalan pengiriman.

## 5. Konfigurasi — `config/pengingat.php`
```php
return [
    'interval_ulang_menit'    => 10,   // ulang push tiap 10 menit
    'wa_pasien_setelah_menit' => 30,   // WA ke pasien bila belum dikonfirmasi
    'wa_pmo_setelah_menit'    => 60,   // libatkan PMO
    'batas_akhir_menit'       => 120,  // berhenti & tandai 'terlewat'
    'kanal' => ['web_push' => true, 'whatsapp' => true],
    'aktif' => ['mo' => true, 'cgd' => true],
    'whatsapp' => [
        'driver' => env('WA_DRIVER', 'log'), // 'log' (dev) | 'cloud_api' (prod)
        'cloud_api' => [
            'token'    => env('WA_CLOUD_TOKEN'),
            'phone_id' => env('WA_CLOUD_PHONE_ID'),
            'template_mo'  => env('WA_TEMPLATE_MO', 'pengingat_obat'),
            'template_cgd' => env('WA_TEMPLATE_CGD', 'pengingat_cgd'),
        ],
    ],
];
```

## 6. Mesin Tick & Logika Eskalasi

Command `php artisan pengingat:tick` didaftarkan di `routes/console.php`
(`->everyMinute()`). Setiap tick:

**Fase 1 — Materialisasi kejadian jatuh tempo.** Ambil jadwal MO & CGD `aktif`, hitung
`slot_jam_harian` hari ini (logika sudah ada di model). Untuk slot yang `waktu_jadwal`
sudah lewat tapi masih dalam `batas_akhir_menit`, lakukan `firstOrCreate` baris
`pengingat_kejadian` (idempoten via unique key). Baris baru berstatus `menunggu`.

**Fase 2 — Proses kejadian `status = menunggu`.** Hitung `selisih = sekarang −
waktu_jadwal` (menit). Tabel keputusan:

| Kondisi | Aksi |
|---|---|
| `selisih > batas_akhir` (120) | set `status = terlewat`, stop |
| Jeda < `interval_ulang` (10) sejak `terakhir_dikirim_pada` | skip (belum waktunya ulang) |
| Pasien **punya push aktif** & `selisih < wa_pasien_setelah` (30) | kirim **Web Push** ke pasien |
| Pasien **tanpa push** (iOS/Safari/belum izinkan) | kirim **WA** ke pasien sejak menit-0 |
| `selisih ≥ wa_pasien_setelah` (30) | kirim **WA** ke pasien (push tetap diulang bila ada) |
| `selisih ≥ wa_pmo_setelah` (60) & `eskalasi_pmo = false` | kirim **WA (+push bila ada) ke PMO**, set `eskalasi_pmo = true` |

Tiap aksi kirim → dispatch **`KirimPengingatJob`** (queued), naikkan penghitung, set
`terakhir_dikirim_pada = sekarang`.

**Alasan pakai job antrian:** tick tetap cepat & tidak macet bila API lambat; job bisa
retry; beban kirim tersebar.

**Lapisan:**
- `PengingatTickService` (static) — orkestrasi fase 1 & 2 + keputusan aksi.
- `PengingatKejadianService` + `PengingatKejadianRepository` (static) — query/tulis kejadian.
- `KirimPengingatJob` — memanggil kanal sesuai `(kejadian, kanal, target)`.

## 7. Kanal Pengiriman

### 7.1 Web Push
- Library: `minishlink/web-push` (VAPID).
- Kunci VAPID digenerate sekali, disimpan di `.env`
  (`VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`).
- Frontend: `public/sw.js` (event `push` & `notificationclick`); JS pendaftaran via
  `whenKesehatanReady` → minta izin → `pushManager.subscribe` → `POST /push/subscribe`.
  Tombol "Aktifkan Pengingat" di dashboard pasien.
- Server: `WebPushSender::kirim($subscription, $payload)`; loop subscription milik user.
  **Respon 404/410 → hapus subscription kedaluwarsa.**
- Payload contoh: "Waktunya minum **Metformin** 💊 — jam 08:00" + URL konfirmasi.

### 7.2 WhatsApp (driver swappable)
- Interface `App\Services\Whatsapp\WhatsAppSender`:
  `kirimTemplate(string $noHp, string $template, array $params): bool`.
- Driver:
  - `LogWhatsAppSender` (dev/lokal) — hanya tulis ke log; tanpa biaya/approval.
  - `CloudApiWhatsAppSender` (produksi) — WhatsApp Cloud API resmi Meta.
- Driver dipilih via `config('pengingat.whatsapp.driver')` (env `WA_DRIVER`).
- **Wajib message template** (pesan business-initiated di luar window 24 jam). Dua template:
  - `pengingat_obat`: "Halo {{1}}, pengingat minum obat {{2}} jadwal jam {{3}}.
    Konfirmasi di aplikasi: {{4}}"
  - `pengingat_cgd`: "Halo {{1}}, pengingat cek gula darah jadwal jam {{2}}.
    Konfirmasi di aplikasi: {{3}}"
  - Template disubmit di Meta Business Manager sebelum produksi.
- Nomor tujuan dari `users.whatsapp_number`, dinormalkan ke format `62…`.
- Env: `WA_DRIVER`, `WA_CLOUD_TOKEN`, `WA_CLOUD_PHONE_ID`, `WA_TEMPLATE_MO`, `WA_TEMPLATE_CGD`.

## 8. Alur Konfirmasi (menutup loop eskalasi)
1. Notifikasi berisi deep-link `/pengingat/{kejadian_id}/konfirmasi`.
2. Bila belum login → diarahkan login lalu balik ke link (session long-lived).
3. Pasien tekan "Sudah minum / Sudah cek" → buat baris di
   `pengingat_mo_logs`/`pengingat_cgd_logs` (modul existing), lalu update kejadian:
   `status = dikonfirmasi`, isi `konfirmasi_log_id` & `dikonfirmasi_pada`.
4. Begitu `status ≠ menunggu`, tick berhenti mengirim untuk kejadian itu (anti-spam).

PMO dapat konfirmasi atas nama pasien (sesuai izin), via endpoint yang sama.

## 9. Sesi & Keamanan
- `SESSION_LIFETIME` diperpanjang (mis. 2 minggu) + "remember me".
- **Tidak ada auto-logout.** Keamanan mengandalkan kunci layar device + idle timeout panjang.

## 10. Error Handling
- `KirimPengingatJob` retry (mis. 3x, backoff) + `failed()` mencatat ke
  `pengingat_kirim_log` (`gagal` + pesan error).
- Push 404/410 → subscription dihapus otomatis.
- WA gagal → dicatat; tidak ada hitungan ganda karena dijaga `terakhir_dikirim_pada`.
- Tick try/catch per-kejadian: satu error tidak menggagalkan seluruh batch.

## 11. Testing (sqlite :memory:, sesuai `composer test`)
- **Unit `PengingatTickService`** (waktu dibekukan `Carbon::setTestNow`): push di menit
  0/10/20, WA pasien di 30, PMO di 60, `terlewat` di 120, berhenti saat dikonfirmasi.
- **Unit pemilihan kanal:** tanpa subscription → WA sejak menit-0; ada subscription → push dulu.
- **Feature:** `/push/subscribe` menyimpan subscription; konfirmasi meng-update kejadian &
  menghentikan kiriman.
- Senders di-fake (`LogWhatsAppSender` + WebPush di-mock) — test tidak menyentuh jaringan.

## 12. Peta File

**Baru:**
- Migrasi: `*_create_push_subscriptions_table`, `*_create_pengingat_kejadian_table`,
  `*_create_pengingat_kirim_log_table`
- `config/pengingat.php`
- `app/Console/Commands/PengingatTick.php`
- `app/Services/PengingatTickService.php`
- `app/Services/PengingatKejadianService.php`
- `app/Repos/PengingatKejadianRepository.php`
- `app/Jobs/KirimPengingatJob.php`
- `app/Services/WebPush/WebPushSender.php`
- `app/Services/Whatsapp/{WhatsAppSender, CloudApiWhatsAppSender, LogWhatsAppSender}.php`
- `app/Models/{PushSubscription, PengingatKejadian}.php`
- `app/Http/Controllers/{PushSubscriptionController, KonfirmasiPengingatController}.php`
- `public/sw.js` + JS pendaftaran subscription
- View halaman konfirmasi pengingat

**Diubah:**
- `routes/console.php` (jadwalkan tick `->everyMinute()`)
- `routes/web.php` (route `/push/subscribe`, `/pengingat/{id}/konfirmasi`)
- `.env` / `.env.example` (VAPID + WA)
- `config/session.php` (lifetime panjang)
- `config/app.php` (timezone `Asia/Jakarta`)
- `composer.json` (require `minishlink/web-push`)

## 13. Kebutuhan Operasional (Deploy)
- Cron menjalankan `php artisan schedule:run` tiap menit.
- Queue worker hidup terus (`php artisan queue:work`, via supervisor di VPS).
- HTTPS wajib (Service Worker & Web Push).
- Rekomendasi: VPS murah. Untuk demo lokal: Task Scheduler Windows / `composer dev` +
  `queue:work`.
