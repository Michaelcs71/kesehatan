# Pengaturan Pengingat Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menu admin untuk mengatur pengingat secara dinamis — MO: jumlah (N), interval (X menit), PMO mulai pengingat ke-M; CGD: toggle aktif, toggle notif "saat dibuat", jam H-1.

**Architecture:** Setting global disimpan satu baris di tabel `pengaturan_pengingat`, dibaca lewat `PengaturanPengingatService::get()` (fallback default bila kosong). `PengingatTickService` memakai nilai ini (bukan `config()`): MO `tentukanAksi` ditulis ulang berbasis **nomor pengingat** (N/X/M); CGD `prosesCgd` membaca jam H-1 & toggle notif-dibuat. UI berupa satu halaman form (Controller→Service), digate permission `pengaturan-pengingat.index/.update`.

**Tech Stack:** Laravel 12, PHP 8.2, MySQL (test: sqlite :memory:), Eloquent, Blade + jQuery + SweetAlert2, Spatie permission.

## Global Constraints

- Domain language Indonesian (nama entitas, method, komentar, pesan validasi, string UI).
- Service & Repository memakai **static methods** eksklusif.
- Controller tipis, extend `BaseController`, kirim `$request->validated()` ke Service, balas `successResponse`/`errorResponse`.
- Primary key UUID (`HasUuids`).
- Mesin MO model baru: pengingat **N kali, tiap X menit**, PMO ikut **setiap** pengingat sejak nomor ≥ M, berhenti saat dikonfirmasi. Default seeding: N=4, X=15, M=3.
- CGD: logika 2x pintar tetap; yang dinamis hanya `cgd_aktif`, `cgd_dibuat_aktif`, `cgd_jam_h1` (default aktif, aktif, '17:00').
- Test dijalankan `php artisan test` (sqlite :memory:); migration harus sqlite-compatible.
- Format `vendor/bin/pint` sebelum commit. Jangan push kecuali diminta.
- Kredensial/teknis (VAPID, template WA, driver) tetap di `config/pengingat.php` — jangan dipindah.

---

### Task 1: Data + Service pengaturan

**Files:**
- Create: `database/migrations/2026_06_21_110000_create_pengaturan_pengingat_table.php`
- Create: `app/Models/PengaturanPengingat.php`
- Create: `database/seeders/PengaturanPengingatSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Create: `app/Services/PengaturanPengingatService.php`
- Test: `tests/Feature/Pengaturan/PengaturanPengingatServiceTest.php`

**Interfaces:**
- Produces:
  - Tabel `pengaturan_pengingat(id, mo_aktif, mo_jumlah, mo_interval_menit, mo_pmo_mulai_ke, cgd_aktif, cgd_dibuat_aktif, cgd_jam_h1, updated_by?, timestamps)`.
  - `App\Models\PengaturanPengingat` dengan cast bool/int, static `defaults(): array`.
  - `PengaturanPengingatService::get(): PengaturanPengingat` (baris pertama, atau instance default bila kosong — TIDAK menyimpan).
  - `PengaturanPengingatService::update(array $data): PengaturanPengingat` (simpan/buat baris, stamp `updated_by`).

- [ ] **Step 1: Tulis test yang gagal**

Create `tests/Feature/Pengaturan/PengaturanPengingatServiceTest.php`:

```php
<?php

namespace Tests\Feature\Pengaturan;

use App\Models\PengaturanPengingat;
use App\Models\User;
use App\Services\PengaturanPengingatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengaturanPengingatServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_mengembalikan_default_saat_tabel_kosong(): void
    {
        $s = PengaturanPengingatService::get();

        $this->assertSame(4, $s->mo_jumlah);
        $this->assertSame(15, $s->mo_interval_menit);
        $this->assertSame(3, $s->mo_pmo_mulai_ke);
        $this->assertTrue($s->mo_aktif);
        $this->assertTrue($s->cgd_aktif);
        $this->assertTrue($s->cgd_dibuat_aktif);
        $this->assertSame('17:00', $s->cgd_jam_h1);
        $this->assertSame(0, PengaturanPengingat::count()); // get() tidak menyimpan
    }

    public function test_update_menyimpan_dan_stamp_updated_by(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $s = PengaturanPengingatService::update([
            'mo_aktif' => true,
            'mo_jumlah' => 6,
            'mo_interval_menit' => 20,
            'mo_pmo_mulai_ke' => 4,
            'cgd_aktif' => false,
            'cgd_dibuat_aktif' => false,
            'cgd_jam_h1' => '18:30',
        ]);

        $this->assertSame(1, PengaturanPengingat::count());
        $this->assertSame(6, $s->fresh()->mo_jumlah);
        $this->assertFalse($s->fresh()->cgd_aktif);
        $this->assertSame($admin->id, $s->fresh()->updated_by);

        // update kedua tidak membuat baris baru
        PengaturanPengingatService::update(['mo_jumlah' => 7] + $s->toArray());
        $this->assertSame(1, PengaturanPengingat::count());
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --filter=PengaturanPengingatServiceTest`
Expected: FAIL ("Class ... PengaturanPengingat not found").

- [ ] **Step 3: Buat migration**

Create `database/migrations/2026_06_21_110000_create_pengaturan_pengingat_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_pengingat', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Minum Obat
            $table->boolean('mo_aktif')->default(true);
            $table->unsignedSmallInteger('mo_jumlah')->default(4);            // N
            $table->unsignedSmallInteger('mo_interval_menit')->default(15);   // X
            $table->unsignedSmallInteger('mo_pmo_mulai_ke')->default(3);      // M

            // Cek Gula Darah
            $table->boolean('cgd_aktif')->default(true);
            $table->boolean('cgd_dibuat_aktif')->default(true);
            $table->string('cgd_jam_h1', 5)->default('17:00');               // 'HH:MM'

            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_pengingat');
    }
};
```

- [ ] **Step 4: Buat model**

Create `app/Models/PengaturanPengingat.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengaturanPengingat extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pengaturan_pengingat';

    protected $fillable = [
        'mo_aktif',
        'mo_jumlah',
        'mo_interval_menit',
        'mo_pmo_mulai_ke',
        'cgd_aktif',
        'cgd_dibuat_aktif',
        'cgd_jam_h1',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'mo_aktif' => 'boolean',
            'mo_jumlah' => 'integer',
            'mo_interval_menit' => 'integer',
            'mo_pmo_mulai_ke' => 'integer',
            'cgd_aktif' => 'boolean',
            'cgd_dibuat_aktif' => 'boolean',
        ];
    }

    /**
     * Nilai default sistem (dipakai service fallback & seeder).
     *
     * @return array<string,mixed>
     */
    public static function defaults(): array
    {
        return [
            'mo_aktif' => true,
            'mo_jumlah' => 4,
            'mo_interval_menit' => 15,
            'mo_pmo_mulai_ke' => 3,
            'cgd_aktif' => true,
            'cgd_dibuat_aktif' => true,
            'cgd_jam_h1' => '17:00',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
```

- [ ] **Step 5: Buat service**

Create `app/Services/PengaturanPengingatService.php`:

```php
<?php

namespace App\Services;

use App\Models\PengaturanPengingat;
use Illuminate\Support\Facades\Auth;

class PengaturanPengingatService
{
    /**
     * Ambil pengaturan; bila belum ada baris, kembalikan instance default
     * (tanpa menyimpan). Query ringan, dipanggil saat tick.
     */
    public static function get(): PengaturanPengingat
    {
        return PengaturanPengingat::query()->first()
            ?? new PengaturanPengingat(PengaturanPengingat::defaults());
    }

    /**
     * Simpan pengaturan (buat baris bila belum ada) + stamp updated_by.
     */
    public static function update(array $data): PengaturanPengingat
    {
        $pengaturan = PengaturanPengingat::query()->first() ?? new PengaturanPengingat;
        $pengaturan->fill($data);
        $pengaturan->updated_by = Auth::id();
        $pengaturan->save();

        return $pengaturan;
    }
}
```

- [ ] **Step 6: Buat seeder + daftarkan**

Create `database/seeders/PengaturanPengingatSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\PengaturanPengingat;
use Illuminate\Database\Seeder;

class PengaturanPengingatSeeder extends Seeder
{
    public function run(): void
    {
        // Idempoten: hanya buat bila belum ada baris.
        if (PengaturanPengingat::query()->doesntExist()) {
            PengaturanPengingat::create(PengaturanPengingat::defaults());
        }
    }
}
```

Modify `database/seeders/DatabaseSeeder.php` — tambah ke array `$this->call([...])` setelah `UserRoleAssignmentSeeder::class,`:

```php
            PengaturanPengingatSeeder::class,   // Baris default pengaturan pengingat
```

- [ ] **Step 7: Jalankan test, pastikan lulus**

Run: `php artisan test --filter=PengaturanPengingatServiceTest`
Expected: PASS (2 test).

- [ ] **Step 8: Format & commit**

```bash
vendor/bin/pint app/Models/PengaturanPengingat.php app/Services/PengaturanPengingatService.php database/seeders/PengaturanPengingatSeeder.php database/seeders/DatabaseSeeder.php
git add database/migrations/2026_06_21_110000_create_pengaturan_pengingat_table.php app/Models/PengaturanPengingat.php app/Services/PengaturanPengingatService.php database/seeders/PengaturanPengingatSeeder.php database/seeders/DatabaseSeeder.php tests/Feature/Pengaturan/PengaturanPengingatServiceTest.php
git commit -m "feat(pengaturan): tabel, model & service pengaturan pengingat"
```

---

### Task 2: Mesin MO berbasis nomor pengingat (N/X/M)

**Files:**
- Modify: `app/Services/PengingatTickService.php` (`tentukanAksi`, `materialisasiMo`, `jalankan`)
- Test (rewrite): `tests/Unit/Pengingat/TentukanAksiTest.php`

**Interfaces:**
- Consumes: `PengaturanPengingatService::get()` → `mo_aktif`, `mo_jumlah`, `mo_interval_menit`, `mo_pmo_mulai_ke`.
- Produces: `tentukanAksi(PengingatKejadian $k, Carbon $now): array{keputusan,aksi}` dengan model baru:
  - `nomor = intdiv($selisih, $X) + 1`; `nomor > N` → `terlewat`.
  - throttle: `terakhir_dikirim_pada` < `$X` menit lalu → `skip`.
  - pasien tiap pengingat: push bila ada subscription, selain itu WA.
  - PMO (WA + push bila ada) pada **setiap** pengingat sejak `nomor >= M` (butuh `user_pmo_id`).
  - `materialisasiMo` jendela = `N * X` menit; `jalankan` pakai `mo_aktif`.

Catatan: nilai `cgd_aktif`/`prosesCgd` diubah di Task 3 — di task ini biarkan baris CGD pada `jalankan()` apa adanya sementara (masih `config('pengingat.aktif.cgd')`), Task 3 yang menyesuaikan.

- [ ] **Step 1: Tulis ulang test (failing)**

Replace seluruh isi `tests/Unit/Pengingat/TentukanAksiTest.php` dengan:

```php
<?php

namespace Tests\Unit\Pengingat;

use App\Models\PengaturanPengingat;
use App\Models\PengingatKejadian;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\PengingatTickService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TentukanAksiTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $jadwal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jadwal = Carbon::parse('2026-06-03 08:00:00');
        // Default: N=4, X=15, M=3
        PengaturanPengingat::create(PengaturanPengingat::defaults());
    }

    private function kejadian(array $override = []): PengingatKejadian
    {
        $pasien = User::factory()->create();
        $pmo = User::factory()->create();

        return new PengingatKejadian(array_merge([
            'jenis' => 'mo', 'jadwal_id' => 'j1', 'id_pasien_pmo' => 'pp1',
            'user_pasien_id' => $pasien->id, 'user_pmo_id' => $pmo->id,
            'waktu_jadwal' => $this->jadwal, 'status' => PengingatKejadian::STATUS_MENUNGGU,
            'eskalasi_pmo' => false, 'terakhir_dikirim_pada' => null,
        ], $override));
    }

    private function beriPush(string $userId): void
    {
        PushSubscription::create(['user_id' => $userId, 'endpoint' => 'https://e/'.$userId, 'public_key' => 'p', 'auth_token' => 'a']);
    }

    public function test_pengingat_pertama_punya_push_kirim_push_pasien(): void
    {
        $k = $this->kejadian();
        $this->beriPush($k->user_pasien_id);

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(0));

        $this->assertSame('kirim', $hasil['keputusan']);
        $this->assertSame([['kanal' => 'push', 'target' => 'pasien']], $hasil['aksi']);
    }

    public function test_pengingat_pertama_tanpa_push_kirim_wa_pasien(): void
    {
        $k = $this->kejadian();

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(0));

        $this->assertSame([['kanal' => 'whatsapp', 'target' => 'pasien']], $hasil['aksi']);
    }

    public function test_skip_bila_belum_lewat_interval(): void
    {
        // terakhir kirim 2 menit lalu, interval 15 → skip
        $k = $this->kejadian(['terakhir_dikirim_pada' => $this->jadwal->copy()->addMinutes(2)]);

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(5));

        $this->assertSame('skip', $hasil['keputusan']);
        $this->assertSame([], $hasil['aksi']);
    }

    public function test_belum_libatkan_pmo_sebelum_nomor_m(): void
    {
        // menit 15 → nomor 2 (< M=3): belum ada PMO
        $k = $this->kejadian();

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(15));

        $targets = array_column($hasil['aksi'], 'target');
        $this->assertNotContains('pmo', $targets);
    }

    public function test_libatkan_pmo_sejak_nomor_m(): void
    {
        // menit 30 → nomor 3 (>= M=3): PMO ikut
        $k = $this->kejadian();

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(30));

        $this->assertContains(['kanal' => 'whatsapp', 'target' => 'pmo'], $hasil['aksi']);
    }

    public function test_lewat_jumlah_maksimum_terlewat(): void
    {
        // N=4, X=15 → nomor>4 saat selisih>=60
        $k = $this->kejadian();

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(60));

        $this->assertSame('terlewat', $hasil['keputusan']);
    }

    public function test_pengaturan_kustom_mengubah_ambang_terlewat(): void
    {
        // Ubah N=2, X=10 → nomor>2 saat selisih>=20
        PengaturanPengingat::query()->update(['mo_jumlah' => 2, 'mo_interval_menit' => 10]);
        $k = $this->kejadian();

        $this->assertSame('kirim', PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(10))['keputusan']);
        $this->assertSame('terlewat', PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(20))['keputusan']);
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --filter=TentukanAksiTest`
Expected: FAIL (model lama masih pakai ambang menit; mis. `test_libatkan_pmo_sejak_nomor_m` gagal karena lama baru eskalasi di menit-60).

- [ ] **Step 3: Tulis ulang `tentukanAksi`**

Modify `app/Services/PengingatTickService.php`. Tambah import setelah `use App\Models\PushSubscription;`:

```php
use App\Services\PengaturanPengingatService;
```

Ganti seluruh method `tentukanAksi` dengan:

```php
    public static function tentukanAksi(PengingatKejadian $k, Carbon $now): array
    {
        $s = PengaturanPengingatService::get();
        $jumlah = (int) $s->mo_jumlah;                       // N
        $interval = max(1, (int) $s->mo_interval_menit);     // X
        $pmoMulaiKe = (int) $s->mo_pmo_mulai_ke;             // M

        $selisih = intdiv($now->getTimestamp() - $k->waktu_jadwal->getTimestamp(), 60);

        // Nomor pengingat ke-berapa (1-based)
        $nomor = intdiv($selisih, $interval) + 1;

        if ($nomor > $jumlah) {
            return ['keputusan' => 'terlewat', 'aksi' => []];
        }

        // Throttle: jangan kirim ulang sebelum interval berlalu sejak terakhir
        if ($k->terakhir_dikirim_pada) {
            $sejakTerakhir = intdiv($now->getTimestamp() - $k->terakhir_dikirim_pada->getTimestamp(), 60);
            if ($sejakTerakhir < $interval) {
                return ['keputusan' => 'skip', 'aksi' => []];
            }
        }

        $pasienPunyaPush = PushSubscription::where('user_id', $k->user_pasien_id)->exists();
        $pmoPunyaPush = $k->user_pmo_id && PushSubscription::where('user_id', $k->user_pmo_id)->exists();

        $aksi = [];

        // --- Kanal pasien: tiap pengingat, push bila ada, selain itu WA ---
        $aksi[] = $pasienPunyaPush
            ? ['kanal' => 'push', 'target' => 'pasien']
            : ['kanal' => 'whatsapp', 'target' => 'pasien'];

        // --- PMO: ikut tiap pengingat sejak nomor >= M ---
        if ($k->user_pmo_id && $nomor >= $pmoMulaiKe) {
            $aksi[] = ['kanal' => 'whatsapp', 'target' => 'pmo'];
            if ($pmoPunyaPush) {
                $aksi[] = ['kanal' => 'push', 'target' => 'pmo'];
            }
        }

        return ['keputusan' => 'kirim', 'aksi' => $aksi];
    }
```

- [ ] **Step 4: Sesuaikan `materialisasiMo` & `jalankan`**

Dalam `app/Services/PengingatTickService.php`:

Ganti baris pembuka `materialisasiMo()` yang mengambil batas. Cari:

```php
        $batas = (int) config('pengingat.batas_akhir_menit');
```

ganti menjadi:

```php
        $s = PengaturanPengingatService::get();
        $batas = (int) $s->mo_jumlah * max(1, (int) $s->mo_interval_menit);
```

Lalu ganti method `jalankan()`. Cari:

```php
        if (config('pengingat.aktif.mo')) {
            self::materialisasiMo();
        }
        self::proses();

        if (config('pengingat.aktif.cgd')) {
            self::prosesCgd();
        }
```

ganti menjadi:

```php
        $s = PengaturanPengingatService::get();

        if ($s->mo_aktif) {
            self::materialisasiMo();
        }
        self::proses();

        if ($s->cgd_aktif) {
            self::prosesCgd();
        }
```

- [ ] **Step 5: Jalankan test MO, pastikan lulus**

Run: `php artisan test --filter=TentukanAksiTest`
Expected: PASS (7 test).

- [ ] **Step 6: Pastikan test tick MO lain tidak regresi**

Run: `php artisan test --filter=PengingatTickServiceTest`
Expected: PASS. (Default N=4,X=15 → kejadian -1mnt = nomor 1 (kirim, 1 job WA pasien), kejadian -200mnt = nomor>4 (terlewat). Test ini butuh baris pengaturan; karena `PengingatTickServiceTest` pakai `RefreshDatabase` tanpa seed, `get()` mengembalikan default in-memory — tidak masalah, nilainya tetap N=4/X=15.)

- [ ] **Step 7: Format & commit**

```bash
vendor/bin/pint app/Services/PengingatTickService.php
git add app/Services/PengingatTickService.php tests/Unit/Pengingat/TentukanAksiTest.php
git commit -m "feat(pengaturan): mesin MO berbasis jumlah/interval/PMO-mulai-ke dari pengaturan"
```

---

### Task 3: CGD baca pengaturan (jam H-1 + toggle notif dibuat)

**Files:**
- Modify: `app/Services/PengingatTickService.php` (`prosesCgd`)
- Test: `tests/Feature/Pengingat/PengingatCgdPengaturanTest.php`

**Interfaces:**
- Consumes: `PengaturanPengingatService::get()` → `cgd_jam_h1`, `cgd_dibuat_aktif`.
- Produces: `prosesCgd()` memakai `cgd_jam_h1` (ganti `config('pengingat.cgd.jam_h1')`) dan melewati fase 'dibuat' bila `cgd_dibuat_aktif` false. Logika 2x + skip same-day H-1 tetap.

- [ ] **Step 1: Tulis test yang gagal**

Create `tests/Feature/Pengingat/PengingatCgdPengaturanTest.php`:

```php
<?php

namespace Tests\Feature\Pengingat;

use App\Jobs\KirimPengingatCgdJob;
use App\Models\JadwalCgd;
use App\Models\JadwalCgdPeserta;
use App\Models\PasienPmo;
use App\Models\PengaturanPengingat;
use App\Services\PengingatTickService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PengingatCgdPengaturanTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pesertaBaru(string $tglJadwal): JadwalCgdPeserta
    {
        $pp = PasienPmo::factory()->create();
        $jadwal = JadwalCgd::factory()->create(['tgl_jadwal_cgd' => $tglJadwal, 'status' => 'aktif']);

        return JadwalCgdPeserta::factory()->create([
            'jadwal_cgd_id' => $jadwal->id,
            'id_pasien_pmo' => $pp->id,
        ]);
    }

    public function test_fase_dibuat_dilewati_saat_cgd_dibuat_aktif_false(): void
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::parse('2026-06-21 09:00:00'));
        PengaturanPengingat::create(PengaturanPengingat::defaults() + []);
        PengaturanPengingat::query()->update(['cgd_dibuat_aktif' => false]);

        $peserta = $this->pesertaBaru('2026-06-30'); // jauh hari → hanya fase 'dibuat' yang relevan

        PengingatTickService::prosesCgd();

        Queue::assertNotPushed(fn (KirimPengingatCgdJob $j) => $j->fase === 'dibuat');
        $this->assertNull($peserta->refresh()->dikirim_dibuat_pada);
    }

    public function test_jam_h1_dari_pengaturan_dipakai(): void
    {
        Queue::fake();
        // Set jam H-1 = 20:00. Event 2026-06-22 → H-1 gate = 2026-06-21 20:00.
        PengaturanPengingat::create(PengaturanPengingat::defaults());
        PengaturanPengingat::query()->update(['cgd_jam_h1' => '20:00']);

        $peserta = $this->pesertaBaru('2026-06-22');
        $peserta->forceFill(['dikirim_dibuat_pada' => now()->subDay()])->save();

        // Jam 19:00 → belum lewat gate 20:00 → tidak kirim h1
        Carbon::setTestNow(Carbon::parse('2026-06-21 19:00:00'));
        PengingatTickService::prosesCgd();
        Queue::assertNotPushed(fn (KirimPengingatCgdJob $j) => $j->fase === 'h1');

        // Jam 20:30 → sudah lewat gate → kirim h1
        Carbon::setTestNow(Carbon::parse('2026-06-21 20:30:00'));
        PengingatTickService::prosesCgd();
        Queue::assertPushed(fn (KirimPengingatCgdJob $j) => $j->fase === 'h1' && $j->pesertaId === $peserta->id);
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --filter=PengingatCgdPengaturanTest`
Expected: FAIL (prosesCgd masih baca config jam_h1 '17:00' & selalu kirim fase dibuat).

- [ ] **Step 3: Ubah `prosesCgd`**

Modify `app/Services/PengingatTickService.php`. Di method `prosesCgd()`, cari:

```php
        $now = Carbon::now();
        $jamH1 = (string) config('pengingat.cgd.jam_h1', '17:00');
```

ganti menjadi:

```php
        $now = Carbon::now();
        $s = PengaturanPengingatService::get();
        $jamH1 = (string) $s->cgd_jam_h1;
        $dibuatAktif = (bool) $s->cgd_dibuat_aktif;
```

Lalu di dalam loop peserta, cari blok fase 'dibuat':

```php
                        if ($peserta->dikirim_dibuat_pada === null) {
                            self::dispatchCgd($peserta, 'dibuat', 'dikirim_dibuat_pada', $now);
                        }
```

ganti menjadi:

```php
                        if ($dibuatAktif && $peserta->dikirim_dibuat_pada === null) {
                            self::dispatchCgd($peserta, 'dibuat', 'dikirim_dibuat_pada', $now);
                        }
```

(Biarkan blok fase 'h1' yang sudah memakai `$jamH1` apa adanya.)

- [ ] **Step 4: Jalankan test, pastikan lulus**

Run: `php artisan test --filter=PengingatCgdPengaturanTest`
Expected: PASS (2 test).

- [ ] **Step 5: Pastikan test CGD lama tidak regresi**

Run: `php artisan test --filter=PengingatCgdTickTest`
Expected: PASS. (Test lama tidak membuat baris pengaturan → `get()` default `cgd_dibuat_aktif=true`, `cgd_jam_h1='17:00'` → perilaku sama seperti sebelumnya.)

- [ ] **Step 6: Format & commit**

```bash
vendor/bin/pint app/Services/PengingatTickService.php
git add app/Services/PengingatTickService.php tests/Feature/Pengingat/PengingatCgdPengaturanTest.php
git commit -m "feat(pengaturan): CGD baca jam H-1 & toggle notif-dibuat dari pengaturan"
```

---

### Task 4: Permission + Controller + Request + Route

**Files:**
- Modify: `database/seeders/RolePermissionSeeder.php` (2 permission baru + grant ke admin)
- Create: `app/Http/Controllers/PengaturanPengingatController.php`
- Create: `app/Http/Requests/PengaturanPengingat/UpdateRequest.php`
- Modify: `routes/web.php` (import + route group)
- Test: `tests/Feature/Pengaturan/PengaturanPengingatRouteTest.php`

**Interfaces:**
- Consumes: `PengaturanPengingatService::get()/update()`.
- Produces:
  - Permission `pengaturan-pengingat.index`, `pengaturan-pengingat.update`.
  - `GET /pengaturan-pengingat` (name `pengaturan-pengingat.index`) → view dengan `$pengaturan`.
  - `PUT/PATCH /pengaturan-pengingat` (name `pengaturan-pengingat.update`) → JSON `successResponse`.

- [ ] **Step 1: Tulis test yang gagal**

Create `tests/Feature/Pengaturan/PengaturanPengingatRouteTest.php`:

```php
<?php

namespace Tests\Feature\Pengaturan;

use App\Models\PengaturanPengingat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengaturanPengingatRouteTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        // Superadmin: User::hasPermissionTo override selalu true → lolos middleware.
        return User::factory()->create(['role' => 'superadmin']);
    }

    public function test_update_menyimpan_pengaturan(): void
    {
        $res = $this->actingAs($this->superadmin())->putJson(route('pengaturan-pengingat.update'), [
            'mo_aktif' => true,
            'mo_jumlah' => 5,
            'mo_interval_menit' => 20,
            'mo_pmo_mulai_ke' => 2,
            'cgd_aktif' => true,
            'cgd_dibuat_aktif' => false,
            'cgd_jam_h1' => '18:00',
        ]);

        $res->assertOk()->assertJson(['success' => true]);
        $this->assertSame(5, PengaturanPengingat::first()->mo_jumlah);
        $this->assertSame('18:00', PengaturanPengingat::first()->cgd_jam_h1);
    }

    public function test_validasi_pmo_mulai_ke_tidak_boleh_lebih_dari_jumlah(): void
    {
        $res = $this->actingAs($this->superadmin())->putJson(route('pengaturan-pengingat.update'), [
            'mo_aktif' => true,
            'mo_jumlah' => 3,
            'mo_interval_menit' => 15,
            'mo_pmo_mulai_ke' => 5, // > jumlah
            'cgd_aktif' => true,
            'cgd_dibuat_aktif' => true,
            'cgd_jam_h1' => '17:00',
        ]);

        $res->assertStatus(422)->assertJsonValidationErrors(['mo_pmo_mulai_ke']);
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --filter=PengaturanPengingatRouteTest`
Expected: FAIL (route tidak ada).

- [ ] **Step 3: Tambah permission di seeder**

Modify `database/seeders/RolePermissionSeeder.php`.

Di array `$permissions`, tambah setelah `'laporan-kepatuhan.index',`:

```php

        // ===== PENGATURAN PENGINGAT =====
        'pengaturan-pengingat.index',
        'pengaturan-pengingat.update',
```

Di `$rolePermissions['admin']`, tambah setelah `'laporan-kepatuhan.index',`:

```php
            // Pengaturan
            'pengaturan-pengingat.index',
            'pengaturan-pengingat.update',
```

- [ ] **Step 4: Buat FormRequest**

Create `app/Http/Requests/PengaturanPengingat/UpdateRequest.php`:

```php
<?php

namespace App\Http\Requests\PengaturanPengingat;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pengaturan-pengingat.update');
    }

    public function rules(): array
    {
        return [
            'mo_aktif' => 'required|boolean',
            'mo_jumlah' => 'required|integer|min:1|max:20',
            'mo_interval_menit' => 'required|integer|min:1|max:180',
            'mo_pmo_mulai_ke' => 'required|integer|min:1|lte:mo_jumlah',
            'cgd_aktif' => 'required|boolean',
            'cgd_dibuat_aktif' => 'required|boolean',
            'cgd_jam_h1' => 'required|date_format:H:i',
        ];
    }

    public function messages(): array
    {
        return [
            'mo_jumlah.required' => 'Jumlah pengingat MO wajib diisi.',
            'mo_jumlah.min' => 'Jumlah pengingat MO minimal 1.',
            'mo_jumlah.max' => 'Jumlah pengingat MO maksimal 20.',
            'mo_interval_menit.required' => 'Interval pengingat MO wajib diisi.',
            'mo_interval_menit.min' => 'Interval minimal 1 menit.',
            'mo_interval_menit.max' => 'Interval maksimal 180 menit.',
            'mo_pmo_mulai_ke.required' => 'Pengingat ke-berapa PMO mulai dilibatkan wajib diisi.',
            'mo_pmo_mulai_ke.lte' => 'PMO mulai dilibatkan tidak boleh melebihi jumlah pengingat.',
            'cgd_jam_h1.required' => 'Jam pengingat H-1 wajib diisi.',
            'cgd_jam_h1.date_format' => 'Format jam H-1 harus HH:MM (contoh: 17:00).',
        ];
    }
}
```

- [ ] **Step 5: Buat controller**

Create `app/Http/Controllers/PengaturanPengingatController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\PengaturanPengingat\UpdateRequest;
use App\Services\PengaturanPengingatService;
use Illuminate\Http\JsonResponse;

class PengaturanPengingatController extends BaseController
{
    public function index()
    {
        $pengaturan = PengaturanPengingatService::get();

        return view('pengaturan-pengingat.index', compact('pengaturan'));
    }

    public function update(UpdateRequest $request): JsonResponse
    {
        try {
            PengaturanPengingatService::update($request->validated());

            return $this->successResponse('Pengaturan pengingat berhasil disimpan.');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Gagal menyimpan pengaturan.');
        }
    }
}
```

- [ ] **Step 6: Tambah route**

Modify `routes/web.php`.

Di blok import controller di atas (dekat `use App\Http\Controllers\JadwalCgdController;`), tambah:

```php
use App\Http\Controllers\PengaturanPengingatController;
```

Tambah grup route baru (mis. setelah grup `jadwal-cgd` ditutup, di dalam scope `auth`+`verified` — boleh grup sendiri):

```php
Route::middleware(['auth', 'verified'])->prefix('pengaturan-pengingat')->name('pengaturan-pengingat.')->group(function () {
    Route::middleware('permission:pengaturan-pengingat.index')
        ->get('/', [PengaturanPengingatController::class, 'index'])->name('index');

    Route::middleware('permission:pengaturan-pengingat.update')
        ->match(['put', 'patch'], '/', [PengaturanPengingatController::class, 'update'])->name('update');
});
```

- [ ] **Step 7: Jalankan test, pastikan lulus**

Run: `php artisan test --filter=PengaturanPengingatRouteTest`
Expected: PASS (2 test).

- [ ] **Step 8: Format & commit**

```bash
vendor/bin/pint app/Http/Controllers/PengaturanPengingatController.php app/Http/Requests/PengaturanPengingat/UpdateRequest.php database/seeders/RolePermissionSeeder.php
git add app/Http/Controllers/PengaturanPengingatController.php app/Http/Requests/PengaturanPengingat/UpdateRequest.php database/seeders/RolePermissionSeeder.php routes/web.php tests/Feature/Pengaturan/PengaturanPengingatRouteTest.php
git commit -m "feat(pengaturan): permission, controller, request & route pengaturan pengingat"
```

---

### Task 5: UI form + menu sidebar

**Files:**
- Create: `resources/views/pengaturan-pengingat/index.blade.php`
- Modify: `resources/views/components/sidebar.blade.php` (item menu)

**Interfaces:**
- Consumes: `$pengaturan` (PengaturanPengingat) dari controller; route `pengaturan-pengingat.update`.
- Produces: form server-rendered nilai saat ini, submit AJAX PUT JSON.

Catatan: tidak ada test otomatis untuk Blade — verifikasi manual di Step akhir.

- [ ] **Step 1: Buat blade form**

Create `resources/views/pengaturan-pengingat/index.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Pengaturan Pengingat')

@section('page-header')
    <div>
        <h4 class="fw-bold mb-1">Pengaturan Pengingat</h4>
        <small class="text-muted">Atur jumlah, interval, dan eskalasi pengingat Minum Obat & Cek Gula Darah.</small>
    </div>
@endsection

@section('content')
    <form id="pengaturanForm" novalidate>
        @csrf
        @method('PUT')

        <div class="row g-4">
            {{-- ============ MINUM OBAT ============ --}}
            <div class="col-lg-6">
                <x-card title="Pengingat Minum Obat" icon="ri-capsule-line">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="mo_aktif" name="mo_aktif"
                            {{ $pengaturan->mo_aktif ? 'checked' : '' }}>
                        <label class="form-check-label" for="mo_aktif">Aktifkan pengingat Minum Obat</label>
                    </div>

                    <div class="mb-3">
                        <label for="mo_jumlah" class="form-label form-label-required">Jumlah pengingat</label>
                        <input type="number" min="1" max="20" id="mo_jumlah" name="mo_jumlah" class="form-control"
                            value="{{ $pengaturan->mo_jumlah }}" required>
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">Berapa kali pengingat dikirim sampai dikonfirmasi (1–20).</small>
                    </div>

                    <div class="mb-3">
                        <label for="mo_interval_menit" class="form-label form-label-required">Interval (menit)</label>
                        <input type="number" min="1" max="180" id="mo_interval_menit" name="mo_interval_menit"
                            class="form-control" value="{{ $pengaturan->mo_interval_menit }}" required>
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">Jeda antar pengingat (1–180 menit).</small>
                    </div>

                    <div class="mb-0">
                        <label for="mo_pmo_mulai_ke" class="form-label form-label-required">PMO mulai dilibatkan pada pengingat ke-</label>
                        <input type="number" min="1" id="mo_pmo_mulai_ke" name="mo_pmo_mulai_ke" class="form-control"
                            value="{{ $pengaturan->mo_pmo_mulai_ke }}" required>
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">PMO ikut dikirimi mulai pengingat ke-berapa (≤ jumlah pengingat).</small>
                    </div>
                </x-card>
            </div>

            {{-- ============ CEK GULA DARAH ============ --}}
            <div class="col-lg-6">
                <x-card title="Pengingat Cek Gula Darah" icon="ri-test-tube-line">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="cgd_aktif" name="cgd_aktif"
                            {{ $pengaturan->cgd_aktif ? 'checked' : '' }}>
                        <label class="form-check-label" for="cgd_aktif">Aktifkan pengingat Cek Gula Darah</label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="cgd_dibuat_aktif" name="cgd_dibuat_aktif"
                            {{ $pengaturan->cgd_dibuat_aktif ? 'checked' : '' }}>
                        <label class="form-check-label" for="cgd_dibuat_aktif">Kirim notifikasi saat jadwal dibuat/diaktifkan</label>
                    </div>

                    <div class="mb-0">
                        <label for="cgd_jam_h1" class="form-label form-label-required">Jam kirim pengingat H-1</label>
                        <input type="time" id="cgd_jam_h1" name="cgd_jam_h1" class="form-control"
                            value="{{ $pengaturan->cgd_jam_h1 }}" required>
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">Jam pengiriman pengingat sehari sebelum jadwal CGD.</small>
                    </div>
                    <div class="alert alert-info small mt-3 mb-0">
                        Jumlah pengingat CGD ditentukan otomatis: notifikasi saat dibuat (bila jauh hari) + 1× H-1.
                    </div>
                </x-card>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary fw-semibold px-4" id="submitBtn">
                <span class="spinner-border spinner-border-sm d-none me-2"></span>
                <i class="ri ri-save-line me-1"></i> Simpan Pengaturan
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        window.whenKesehatanReady(function() {
            'use strict';

            const $form = $('#pengaturanForm');
            const $submitBtn = $('#submitBtn');
            const UPDATE_URL = '{{ route('pengaturan-pengingat.update') }}';

            $form.on('submit', async function(e) {
                e.preventDefault();

                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.invalid-feedback').text('');
                $submitBtn.prop('disabled', true).find('.spinner-border').removeClass('d-none');

                const data = {
                    _method: 'PUT',
                    mo_aktif: $('#mo_aktif').is(':checked'),
                    mo_jumlah: parseInt($('#mo_jumlah').val(), 10),
                    mo_interval_menit: parseInt($('#mo_interval_menit').val(), 10),
                    mo_pmo_mulai_ke: parseInt($('#mo_pmo_mulai_ke').val(), 10),
                    cgd_aktif: $('#cgd_aktif').is(':checked'),
                    cgd_dibuat_aktif: $('#cgd_dibuat_aktif').is(':checked'),
                    cgd_jam_h1: $('#cgd_jam_h1').val(),
                };

                const csrfToken = $('input[name=_token]').val() || $('meta[name=csrf-token]').attr('content');

                try {
                    const res = await $.ajax({
                        url: UPDATE_URL,
                        method: 'POST',
                        contentType: 'application/json',
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        data: JSON.stringify(data),
                    });

                    Swal.fire({
                        title: 'Berhasil!',
                        text: res.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false,
                    });
                } catch (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Object.entries(xhr.responseJSON.errors).forEach(function([field, messages]) {
                            const $field = $('#' + field);
                            $field.addClass('is-invalid');
                            $field.siblings('.invalid-feedback').text(messages[0]);
                        });
                        Swal.fire('Validasi Gagal', 'Mohon periksa kembali isian Anda.', 'warning');
                    } else {
                        Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                    }
                } finally {
                    $submitBtn.prop('disabled', false).find('.spinner-border').addClass('d-none');
                }
            });
        });
    </script>
@endpush
```

- [ ] **Step 2: Tambah item menu sidebar**

Modify `resources/views/components/sidebar.blade.php`. Sebelum blok `{{-- ============ AKUN ============ --}}` (sekitar baris 161), tambah:

```blade
    {{-- ============ PENGATURAN ============ --}}
    @can('pengaturan-pengingat.index')
        <li class="nav-title">Pengaturan</li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('pengaturan-pengingat.*') ? 'active' : '' }}"
                href="{{ route('pengaturan-pengingat.index') }}">
                <span class="nav-icon"><i class="ri ri-settings-3-line"></i></span> Pengaturan Pengingat
            </a>
        </li>
    @endcan

```

- [ ] **Step 3: Build asset & verifikasi manual**

Run: `npm run build`
Expected: build sukses tanpa error.

Verifikasi manual (login admin/superadmin):
1. Menu "Pengaturan Pengingat" muncul di sidebar.
2. Buka `/pengaturan-pengingat` → form menampilkan nilai sekarang (default N=4, X=15, M=3, CGD aktif, 17:00).
3. Ubah nilai → Simpan → notifikasi sukses; reload → nilai tersimpan.
4. Isi `mo_pmo_mulai_ke` > `mo_jumlah` → error validasi tampil di field.
5. Pastikan role pasien/pmo TIDAK melihat menu ini.

- [ ] **Step 4: Commit**

```bash
git add resources/views/pengaturan-pengingat/index.blade.php resources/views/components/sidebar.blade.php
git commit -m "feat(pengaturan): UI form pengaturan pengingat + menu sidebar"
```

---

### Task 6: Verifikasi menyeluruh

**Files:** (tidak ada perubahan kode; gerbang akhir)

- [ ] **Step 1: Jalankan seluruh test**

Run: `php artisan test`
Expected: Semua hijau kecuali 2 test auth Breeze pre-existing (lihat memori `auth-tests-pre-existing-fail`). Tidak ada regresi baru di modul pengingat MO/CGD.

- [ ] **Step 2: Lint**

Run: `vendor/bin/pint --test`
Expected: file-file fitur ini bersih (abaikan temuan pre-existing di `app_backup_before_mysql/` & file yang tak disentuh).

- [ ] **Step 3: Cek seeder pengaturan & permission**

Run: `php artisan migrate && php artisan db:seed --class=PengaturanPengingatSeeder && php artisan db:seed --class=RolePermissionSeeder`
Expected: sukses; tabel `pengaturan_pengingat` punya 1 baris; permission `pengaturan-pengingat.*` ada. (Gunakan DB dev; ini bukan `migrate:fresh`, data lain aman.)

- [ ] **Step 4: Commit penutup (bila ada perubahan format)**

```bash
git add -A
git commit -m "chore(pengaturan): rapikan format & verifikasi akhir"
```

---

## Catatan

- Nilai lama di `config/pengingat.php` (`interval_ulang_menit`, `batas_akhir_menit`, `wa_pasien_setelah_menit`, `wa_pmo_setelah_menit`, `aktif.mo`, `aktif.cgd`, `cgd.jam_h1`) **tidak lagi dibaca** mesin setelah perubahan ini, tapi dibiarkan di config (tidak menghapus untuk meminimalkan blast radius). Kredensial/teknis (VAPID, template WA, driver) tetap dipakai dari config.
- Penyederhanaan dari spec: `PengaturanPengingatService::get()` query per panggilan tanpa cache statis (spec menyebut cache per-request). Biaya query sangat kecil dan menghindari kerumitan invalidasi/test; bila kelak jadi bottleneck, tambahkan memoize.

## Self-Review

- **Spec coverage:** tabel single-row + model + service get/update (T1) ✓; default seeding N=4/X=15/M=3 & CGD (T1) ✓; MO model N/X/M berbasis nomor + channel pasien push→WA + PMO sejak ke-M + materialisasi window + jalankan toggle (T2) ✓; CGD jam_h1 + toggle dibuat + cgd_aktif (T3, T2 jalankan) ✓; permission + controller + request + route + validasi lte (T4) ✓; UI form + sidebar (T5) ✓; verifikasi (T6) ✓. Kredensial tetap di config ✓.
- **Placeholder scan:** tidak ada TBD/TODO; semua step berisi kode/perintah konkret.
- **Type consistency:** `PengaturanPengingatService::get(): PengaturanPengingat` & `update(array): PengaturanPengingat` konsisten dipakai di T2/T3/T4; field `mo_jumlah/mo_interval_menit/mo_pmo_mulai_ke/cgd_aktif/cgd_dibuat_aktif/cgd_jam_h1` konsisten antar migration, model defaults, service, mesin, request, blade. `tentukanAksi(PengingatKejadian,Carbon):array` signature tidak berubah (hanya isi).
