# Desain: Menu Pengaturan Pengingat (dinamis)

Tanggal: 2026-06-21
Status: disetujui (siap rencana implementasi)

## Latar belakang

Nilai pengingat saat ini hard-coded di `config/pengingat.php` (lewat env): interval ulang
MO, batas akhir, ambang WA pasien/PMO, jam H-1 CGD, dan toggle `aktif.mo`/`aktif.cgd`.
Dikonsumsi di `app/Services/PengingatTickService.php` (`tentukanAksi`, `materialisasiMo`,
`prosesCgd`, `jalankan`).

Tujuan: admin bisa **mengatur dari UI** berapa kali pengingat dikirim dan intervalnya,
tanpa edit file/redeploy.

## Keputusan desain (dikonfirmasi user)

1. **Lingkup:** global — satu setting sistem, diatur Admin/Superadmin (bukan per-pasien/per-jadwal).
2. **Model MO:** pengingat dikirim **N kali, tiap X menit**; **PMO mulai ikut dikirimi pada
   pengingat ke-M**; berhenti saat pasien konfirmasi. Menggantikan model lama
   (batas akhir + ambang menit WA pasien/PMO).
3. **Channel pasien MO:** tiap pengingat → Web Push bila ada subscription, selain itu
   WhatsApp. (Ambang "WA mulai menit-30" lama dihapus.)
4. **Model CGD:** tetap logika 2x pintar yang sudah ada (notif saat dibuat bila jauh hari +
   1x H-1; bila dibuat pas H-1 → 1x). Jumlah tidak diubah.
5. **Setting CGD yang bisa diatur:** jam kirim H-1, toggle aktif CGD, toggle notif "saat dibuat".
6. **Default seeding:** MO N=4, interval=15 menit, PMO mulai ke-3. CGD aktif, notif dibuat
   aktif, jam H-1 = 17:00.
7. **Tetap di config (bukan setting user):** VAPID, template WA, driver WA, base URL —
   kredensial/teknis.

## Arsitektur

Pola berlapis konsisten codebase: Controller (tipis) → Service (static) → Repository/Model.
Setting disimpan satu baris di DB, dibaca mesin tick lewat satu service ber-cache.

### 1. Data — tabel single-row `pengaturan_pengingat`

Migration baru. Kolom:

- `id` uuid primary
- `mo_aktif` boolean default true
- `mo_jumlah` unsignedSmallInteger default 4            // N
- `mo_interval_menit` unsignedSmallInteger default 15   // X
- `mo_pmo_mulai_ke` unsignedSmallInteger default 3      // M (1..N)
- `cgd_aktif` boolean default true
- `cgd_dibuat_aktif` boolean default true
- `cgd_jam_h1` string(5) default '17:00'                // 'HH:MM'
- `updated_by` foreignUuid nullable → users
- `timestamps`

Model `PengaturanPengingat` (HasUuids) + cast bool/int. Seeder menyisipkan satu baris
default (dipanggil dari `DatabaseSeeder`, idempoten `firstOrCreate`).

### 2. Service pengaturan — `PengaturanPengingatService` (static)

- `get(): PengaturanPengingat` — ambil baris pertama; bila belum ada, buat dari default
  (atau kembalikan instance default in-memory). Di-cache per-request (static prop) agar
  tick tidak query berulang.
- `update(array $data): PengaturanPengingat` — validasi sudah di FormRequest; simpan,
  stamp `updated_by = Auth::id()`, reset cache.

Mesin membaca lewat service ini, bukan `config()`, untuk nilai dinamis.

### 3. Perubahan mesin (`PengingatTickService`)

- `jalankan()`: baca `mo_aktif` & `cgd_aktif` dari pengaturan (ganti `config('pengingat.aktif.*')`).
- **MO `tentukanAksi`** ditulis ulang berbasis **nomor pengingat**:
  - `nomor = intdiv($selisih, $X) + 1` (X = `mo_interval_menit`).
  - bila `nomor > N` (N = `mo_jumlah`) → `terlewat`.
  - throttle tetap: bila `selisih sejak terakhir < X` → `skip`.
  - kirim ke pasien: push bila ada `PushSubscription`, selain itu WA — tiap pengingat.
  - PMO ikut bila `nomor >= M` (M = `mo_pmo_mulai_ke`) dan `! eskalasi_pmo` belum… →
    kirim WA PMO (+ push bila ada). (Catatan: PMO boleh dikirimi berulang tiap pengingat
    sejak ke-M, atau sekali — lihat "Detail perilaku" di bawah.)
  - berhenti saat status dikonfirmasi (logika existing).
- **CGD `prosesCgd`**: baca `cgd_jam_h1`; bila `! cgd_dibuat_aktif` → lewati fase 'dibuat'
  (tetap kirim H-1). Logika 2x + skip same-day H-1 tetap.

Detail perilaku PMO: PMO dikirimi **setiap** pengingat sejak nomor ≥ M (konsisten dgn
"pasien tiap kali"), karena model lama `eskalasi_pmo` (sekali) tidak lagi dipakai sebagai
ambang. Field `eskalasi_pmo` boleh tetap di-set true saat pertama kirim PMO untuk audit.

### 4. Menu & UI

- Permission baru `pengaturan-pengingat.index` & `pengaturan-pengingat.update`, ditambah ke
  `RolePermissionSeeder` (admin + superadmin; superadmin = `'*'` sudah otomatis).
- Route group `pengaturan-pengingat`: `GET /` (index = form), `PUT /` (update, JSON).
- Item sidebar baru "Pengaturan Pengingat" (gated permission), ikon cog.
- Controller `PengaturanPengingatController` (tipis): `index()` render form;
  `update(UpdateRequest)` → Service::update → JSON success/error (pola BaseController).
- Satu blade `pengaturan-pengingat/index.blade.php`: section MO (mo_aktif toggle,
  mo_jumlah, mo_interval_menit, mo_pmo_mulai_ke) + section CGD (cgd_aktif, cgd_dibuat_aktif,
  cgd_jam_h1). Submit AJAX JSON (axios/jQuery + SweetAlert2) seperti form lain.

### 5. Validasi (FormRequest `UpdateRequest`)

- `mo_aktif`, `cgd_aktif`, `cgd_dibuat_aktif` → `required|boolean`
- `mo_jumlah` → `required|integer|min:1|max:20`
- `mo_interval_menit` → `required|integer|min:1|max:180`
- `mo_pmo_mulai_ke` → `required|integer|min:1|lte:mo_jumlah`
- `cgd_jam_h1` → `required|date_format:H:i`
- pesan dalam Bahasa Indonesia.

## Komponen & batasan

| Unit | Tanggung jawab | Bergantung pada |
|---|---|---|
| `pengaturan_pengingat` (+model) | simpan setting global | users (updated_by) |
| `PengaturanPengingatService` | baca (cache+default) & update setting | model |
| `PengingatTickService` (ubah) | pakai setting dinamis utk MO & CGD | service pengaturan |
| Controller/Request/Blade | UI form + validasi | service pengaturan |
| Seeder + RolePermissionSeeder | default row + permission | — |

## Pengujian

- **Unit MO `tentukanAksi`** (model baru): nomor pengingat benar per interval; `terlewat`
  saat nomor > N; pasien tiap pengingat; PMO mulai saat nomor ≥ M; `skip` saat dalam
  interval; berhenti saat dikonfirmasi. **Test MO lama (`PengingatTickServiceTest`)
  diperbarui** ke model N/X/M.
- **CGD**: `cgd_dibuat_aktif=false` → fase 'dibuat' dilewati, H-1 tetap.
- **Service pengaturan**: fallback default saat tabel kosong; `update` menyimpan + reset cache.
- **Feature**: form `update` menyimpan nilai & meng-update; mesin tick memakai nilai DB
  (mis. set N=2 → pengingat ke-3 jadi `terlewat`).

## Di luar lingkup (YAGNI)

- Pengaturan per-pasien / per-jadwal.
- Mengatur jumlah/interval CGD (tetap logika 2x pintar).
- Memindahkan kredensial (VAPID/WA) ke DB.
- Histori perubahan setting (selain `updated_by`/`updated_at`).
