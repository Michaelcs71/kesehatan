# Pengingat Web Push + WhatsApp (Minum Obat) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun mesin pengingat Minum Obat (MO) yang mengirim Web Push (utama) dan WhatsApp (fallback) secara berulang dengan eskalasi pasien → PMO, berhenti saat dikonfirmasi.

**Architecture:** Opsi A — tabel `pengingat_kejadian` (satu baris per kemunculan slot, idempoten). Command `pengingat:tick` jalan tiap menit: materialisasi slot MO yang jatuh tempo, lalu memproses kejadian `menunggu` berdasar tabel keputusan eskalasi, mendispatch `KirimPengingatJob` ke antrian. Kanal: `WebPushSender` (minishlink/web-push) + `WhatsAppSender` (driver `log` untuk dev, `cloud_api` untuk produksi, swappable). Mengikuti pola berlapis proyek (Service/Repository static, `DB::transaction`).

**Tech Stack:** Laravel 12, PHP 8.2, MySQL (prod) / sqlite :memory: (test), queue `database`, `minishlink/web-push`, WhatsApp Cloud API, PHPUnit, Service Worker + Push API (frontend).

**Cakupan:** Tahap ini **MO saja**. Mesin dibuat generik (`jenis` enum sudah memuat `cgd`) agar CGD dicolok belakangan setelah tautan pasien CGD diberesi. Lihat spec `docs/superpowers/specs/2026-06-03-pengingat-web-push-whatsapp-design.md` §2.1.

**Acuan kode existing:**
- `app/Models/JadwalMinumObat.php` — `getSlotJamHarianAttribute()` menghasilkan array slot jam (mis. `['08:00','16:00']`), `scopeActive()`.
- `app/Models/PasienPmo.php` — `id_user` (pasien), `pmo_user_id` (PMO).
- `app/Models/PengingatMoLog.php` — tujuan konfirmasi; `calculatePatuhMenit($slot,$jam)`.
- `app/Models/User.php` — `whatsapp_number`, `isPasien()`, `isPmo()`.
- `routes/console.php` — tempat mendaftarkan schedule (Laravel 12 pakai `Illuminate\Support\Facades\Schedule`).

---

## Task 1: Dependency, config, timezone & session

**Files:**
- Modify: `composer.json` (via composer require)
- Create: `config/pengingat.php`
- Modify: `config/app.php` (timezone)
- Modify: `config/session.php` (lifetime)
- Modify: `.env`, `.env.example`
- Test: `tests/Unit/Pengingat/ConfigPengingatTest.php`

- [ ] **Step 1: Pasang library Web Push**

Run:
```bash
composer require minishlink/web-push
```
Expected: paket `minishlink/web-push` masuk ke `composer.json` `require`.

- [ ] **Step 2: Tulis test config**

Create `tests/Unit/Pengingat/ConfigPengingatTest.php`:
```php
<?php

namespace Tests\Unit\Pengingat;

use Tests\TestCase;

class ConfigPengingatTest extends TestCase
{
    public function test_config_pengingat_punya_default_yang_benar(): void
    {
        $this->assertSame(10, config('pengingat.interval_ulang_menit'));
        $this->assertSame(30, config('pengingat.wa_pasien_setelah_menit'));
        $this->assertSame(60, config('pengingat.wa_pmo_setelah_menit'));
        $this->assertSame(120, config('pengingat.batas_akhir_menit'));
        $this->assertTrue(config('pengingat.kanal.web_push'));
        $this->assertTrue(config('pengingat.kanal.whatsapp'));
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
    }
}
```

- [ ] **Step 3: Jalankan test, pastikan GAGAL**

Run: `php artisan test --filter=ConfigPengingatTest`
Expected: FAIL (config `pengingat` belum ada / timezone masih `UTC`).

- [ ] **Step 4: Buat `config/pengingat.php`**

```php
<?php

return [
    // Semua satuan menit; diubah di sini tanpa migrasi.
    'interval_ulang_menit'    => env('PENGINGAT_INTERVAL_ULANG', 10),
    'wa_pasien_setelah_menit' => env('PENGINGAT_WA_PASIEN_MENIT', 30),
    'wa_pmo_setelah_menit'    => env('PENGINGAT_WA_PMO_MENIT', 60),
    'batas_akhir_menit'       => env('PENGINGAT_BATAS_AKHIR_MENIT', 120),

    'kanal' => [
        'web_push' => env('PENGINGAT_KANAL_PUSH', true),
        'whatsapp' => env('PENGINGAT_KANAL_WA', true),
    ],

    'aktif' => [
        'mo'  => true,
        'cgd' => false, // menyusul
    ],

    'vapid' => [
        'subject'     => env('VAPID_SUBJECT', 'mailto:admin@kesehatan.test'),
        'public_key'  => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    'whatsapp' => [
        'driver' => env('WA_DRIVER', 'log'), // 'log' (dev) | 'cloud_api' (prod)
        'cloud_api' => [
            'token'        => env('WA_CLOUD_TOKEN'),
            'phone_id'     => env('WA_CLOUD_PHONE_ID'),
            'template_mo'  => env('WA_TEMPLATE_MO', 'pengingat_obat'),
            'lang'         => env('WA_TEMPLATE_LANG', 'id'),
            'base_url'     => env('WA_CLOUD_BASE_URL', 'https://graph.facebook.com/v21.0'),
        ],
    ],
];
```

- [ ] **Step 5: Set timezone**

Di `config/app.php`, ubah baris `'timezone' => 'UTC',` menjadi:
```php
    'timezone' => 'Asia/Jakarta',
```

- [ ] **Step 6: Perpanjang session lifetime**

Di `config/session.php`, ubah default `'lifetime' => env('SESSION_LIFETIME', 120),` menjadi:
```php
    'lifetime' => (int) env('SESSION_LIFETIME', 20160), // 14 hari (menit)
```
Dan pastikan `'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),`.

- [ ] **Step 7: Tambah entri `.env.example` (dan `.env`)**

Tambahkan di akhir `.env.example` dan `.env`:
```env

# Pengingat
SESSION_LIFETIME=20160
VAPID_SUBJECT=mailto:admin@kesehatan.test
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
WA_DRIVER=log
WA_CLOUD_TOKEN=
WA_CLOUD_PHONE_ID=
WA_TEMPLATE_MO=pengingat_obat
WA_TEMPLATE_LANG=id
```

- [ ] **Step 8: Jalankan test, pastikan LULUS**

Run: `php artisan test --filter=ConfigPengingatTest`
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add composer.json composer.lock config/pengingat.php config/app.php config/session.php .env.example tests/Unit/Pengingat/ConfigPengingatTest.php
git commit -m "feat(pengingat): config, timezone Asia/Jakarta, session 14 hari + web-push dep"
```

---

## Task 2: Migrasi & model `PushSubscription`

**Files:**
- Create: `database/migrations/2026_06_03_100000_create_push_subscriptions_table.php`
- Create: `app/Models/PushSubscription.php`
- Test: `tests/Unit/Pengingat/PushSubscriptionTest.php`

- [ ] **Step 1: Tulis test**

```php
<?php

namespace Tests\Unit\Pengingat;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_bisa_membuat_subscription_milik_user(): void
    {
        $user = User::factory()->create();

        $sub = PushSubscription::create([
            'user_id'     => $user->id,
            'endpoint'    => 'https://push.example/abc',
            'public_key'  => 'p256dh-key',
            'auth_token'  => 'auth-key',
            'user_agent'  => 'Chrome',
        ]);

        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => 'https://push.example/abc']);
        $this->assertSame($user->id, $sub->user->id);
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `php artisan test --filter=PushSubscriptionTest`
Expected: FAIL (tabel/model belum ada).

- [ ] **Step 3: Buat migrasi**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('endpoint');
            $table->string('public_key');
            $table->string('auth_token');
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->unique('endpoint', 'uq_push_endpoint');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
```

> Catatan: MySQL tidak mengizinkan `unique` pada `TEXT` tanpa panjang. Jika DB MySQL menolak, ganti `->text('endpoint')` menjadi `->string('endpoint', 500)`. Untuk sqlite (test) keduanya jalan.

- [ ] **Step 4: Buat model `app/Models/PushSubscription.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    use HasUuids;

    protected $table = 'push_subscriptions';

    protected $fillable = [
        'user_id',
        'endpoint',
        'public_key',
        'auth_token',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

- [ ] **Step 5: Jalankan test, pastikan LULUS**

Run: `php artisan test --filter=PushSubscriptionTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_06_03_100000_create_push_subscriptions_table.php app/Models/PushSubscription.php tests/Unit/Pengingat/PushSubscriptionTest.php
git commit -m "feat(pengingat): tabel & model push_subscriptions"
```

---

## Task 3: Migrasi & model `PengingatKejadian`

**Files:**
- Create: `database/migrations/2026_06_03_100001_create_pengingat_kejadian_table.php`
- Create: `app/Models/PengingatKejadian.php`
- Test: `tests/Unit/Pengingat/PengingatKejadianModelTest.php`

- [ ] **Step 1: Tulis test**

```php
<?php

namespace Tests\Unit\Pengingat;

use App\Models\PengingatKejadian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PengingatKejadianModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_status_menunggu_dan_scope_menunggu(): void
    {
        $pasien = User::factory()->create();

        $k = PengingatKejadian::create([
            'jenis'          => 'mo',
            'jadwal_id'      => $pasien->id, // sembarang uuid untuk uji model
            'id_pasien_pmo'  => $pasien->id,
            'user_pasien_id' => $pasien->id,
            'waktu_jadwal'   => Carbon::parse('2026-06-03 08:00:00'),
            'status'         => PengingatKejadian::STATUS_MENUNGGU,
        ]);

        $this->assertSame('menunggu', $k->status);
        $this->assertInstanceOf(Carbon::class, $k->waktu_jadwal);
        $this->assertTrue(PengingatKejadian::menunggu()->whereKey($k->id)->exists());
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `php artisan test --filter=PengingatKejadianModelTest`
Expected: FAIL.

- [ ] **Step 3: Buat migrasi**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengingat_kejadian', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('jenis', 10);              // 'mo' | 'cgd'
            $table->uuid('jadwal_id');                 // ke jadwal MO/CGD sesuai jenis
            $table->uuid('id_pasien_pmo')->nullable();
            $table->uuid('user_pasien_id')->nullable();
            $table->uuid('user_pmo_id')->nullable();

            $table->dateTime('waktu_jadwal');          // tanggal + slot jam (acuan selisih)
            $table->string('status', 15)->default('menunggu'); // menunggu|dikonfirmasi|terlewat

            $table->uuid('konfirmasi_log_id')->nullable();
            $table->dateTime('dikonfirmasi_pada')->nullable();

            $table->unsignedInteger('jumlah_push')->default(0);
            $table->unsignedInteger('jumlah_wa_pasien')->default(0);
            $table->unsignedInteger('jumlah_wa_pmo')->default(0);
            $table->dateTime('terakhir_dikirim_pada')->nullable();
            $table->boolean('eskalasi_pmo')->default(false);

            $table->timestamps();

            $table->unique(['jenis', 'jadwal_id', 'waktu_jadwal'], 'uq_kejadian_slot');
            $table->index(['status', 'waktu_jadwal'], 'idx_status_waktu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengingat_kejadian');
    }
};
```

- [ ] **Step 4: Buat model `app/Models/PengingatKejadian.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengingatKejadian extends Model
{
    use HasUuids;

    public const STATUS_MENUNGGU     = 'menunggu';
    public const STATUS_DIKONFIRMASI = 'dikonfirmasi';
    public const STATUS_TERLEWAT     = 'terlewat';

    protected $table = 'pengingat_kejadian';

    protected $fillable = [
        'jenis',
        'jadwal_id',
        'id_pasien_pmo',
        'user_pasien_id',
        'user_pmo_id',
        'waktu_jadwal',
        'status',
        'konfirmasi_log_id',
        'dikonfirmasi_pada',
        'jumlah_push',
        'jumlah_wa_pasien',
        'jumlah_wa_pmo',
        'terakhir_dikirim_pada',
        'eskalasi_pmo',
    ];

    protected function casts(): array
    {
        return [
            'waktu_jadwal'          => 'datetime',
            'dikonfirmasi_pada'     => 'datetime',
            'terakhir_dikirim_pada' => 'datetime',
            'eskalasi_pmo'          => 'boolean',
            'jumlah_push'           => 'integer',
            'jumlah_wa_pasien'      => 'integer',
            'jumlah_wa_pmo'         => 'integer',
        ];
    }

    public function scopeMenunggu(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_MENUNGGU);
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_pasien_id');
    }

    public function pmo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_pmo_id');
    }
}
```

- [ ] **Step 5: Jalankan test, pastikan LULUS**

Run: `php artisan test --filter=PengingatKejadianModelTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_06_03_100001_create_pengingat_kejadian_table.php app/Models/PengingatKejadian.php tests/Unit/Pengingat/PengingatKejadianModelTest.php
git commit -m "feat(pengingat): tabel & model pengingat_kejadian"
```

---

## Task 4: Migrasi & model `PengingatKirimLog`

**Files:**
- Create: `database/migrations/2026_06_03_100002_create_pengingat_kirim_log_table.php`
- Create: `app/Models/PengingatKirimLog.php`
- Test: `tests/Unit/Pengingat/PengingatKirimLogTest.php`

- [ ] **Step 1: Tulis test**

```php
<?php

namespace Tests\Unit\Pengingat;

use App\Models\PengingatKirimLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengingatKirimLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_bisa_mencatat_kiriman(): void
    {
        $log = PengingatKirimLog::create([
            'kejadian_id' => 'a-uuid',
            'kanal'       => 'whatsapp',
            'target'      => 'pasien',
            'status'      => 'terkirim',
            'error'       => null,
        ]);

        $this->assertDatabaseHas('pengingat_kirim_log', [
            'kanal' => 'whatsapp', 'target' => 'pasien', 'status' => 'terkirim',
        ]);
        $this->assertNotNull($log->id);
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `php artisan test --filter=PengingatKirimLogTest`
Expected: FAIL.

- [ ] **Step 3: Buat migrasi**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengingat_kirim_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kejadian_id');
            $table->string('kanal', 15);   // push | whatsapp
            $table->string('target', 10);  // pasien | pmo
            $table->string('status', 12);  // terkirim | gagal
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('kejadian_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengingat_kirim_log');
    }
};
```

- [ ] **Step 4: Buat model `app/Models/PengingatKirimLog.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PengingatKirimLog extends Model
{
    use HasUuids;

    protected $table = 'pengingat_kirim_log';

    protected $fillable = [
        'kejadian_id',
        'kanal',
        'target',
        'status',
        'error',
    ];
}
```

- [ ] **Step 5: Jalankan test, pastikan LULUS**

Run: `php artisan test --filter=PengingatKirimLogTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_06_03_100002_create_pengingat_kirim_log_table.php app/Models/PengingatKirimLog.php tests/Unit/Pengingat/PengingatKirimLogTest.php
git commit -m "feat(pengingat): tabel & model pengingat_kirim_log"
```

---

## Task 5: Factory dependency MO (untuk test materialisasi & konfirmasi)

**Files:**
- Create: `database/factories/MasterObatFactory.php`
- Create: `database/factories/PasienPmoFactory.php`
- Create: `database/factories/JadwalMinumObatFactory.php`
- Modify: `app/Models/MasterObat.php` (tambah `use HasFactory` jika belum)
- Modify: `app/Models/PasienPmo.php` (sudah `use HasFactory`)
- Modify: `app/Models/JadwalMinumObat.php` (sudah `use HasFactory`)
- Test: `tests/Unit/Pengingat/FactoryMoTest.php`

> Catatan: `MasterObat.kategori_id`, `satuan_id`, `dosis_default` nullable → factory obat cukup `nama` + `created_by`. `PasienPmo` butuh `nama_pasien,nik,nama_pmo,jenis_pmo,tanggal_regis,status_diabetes`. `JadwalMinumObat` butuh `id_pasien_pmo,obat_id,nama_pasien,nama_pmo,tgl_mulai,jam_mulai,frekuensi_per_hari`.

- [ ] **Step 1: Tulis test**

```php
<?php

namespace Tests\Unit\Pengingat;

use App\Models\JadwalMinumObat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoryMoTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_jadwal_mo_membuat_rantai_lengkap(): void
    {
        $jadwal = JadwalMinumObat::factory()->create([
            'jam_mulai'          => '08:00:00',
            'frekuensi_per_hari' => 2,
        ]);

        $this->assertNotNull($jadwal->id_pasien_pmo);
        $this->assertNotNull($jadwal->obat_id);
        $this->assertSame(['08:00', '20:00'], $jadwal->slot_jam_harian);
        $this->assertNotNull($jadwal->pasienPmo->id_user);
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `php artisan test --filter=FactoryMoTest`
Expected: FAIL (factory belum ada).

- [ ] **Step 3: Pastikan model pakai `HasFactory`**

Buka `app/Models/MasterObat.php`. Jika belum ada, tambahkan di `use` class:
```php
use Illuminate\Database\Eloquent\Factories\HasFactory;
```
dan pada deklarasi trait kelas tambahkan `HasFactory` (mis. `use HasFactory, SoftDeletes, HasUuids;`). `PasienPmo` & `JadwalMinumObat` sudah memakai `HasFactory` (verifikasi, tidak perlu ubah bila sudah ada).

- [ ] **Step 4: Buat `database/factories/MasterObatFactory.php`**

```php
<?php

namespace Database\Factories;

use App\Models\MasterObat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MasterObatFactory extends Factory
{
    protected $model = MasterObat::class;

    public function definition(): array
    {
        return [
            'nama'       => 'Metformin ' . $this->faker->numberBetween(1, 9999),
            'status'     => 'approved',
            'created_by' => User::factory(),
        ];
    }
}
```

- [ ] **Step 5: Buat `database/factories/PasienPmoFactory.php`**

```php
<?php

namespace Database\Factories;

use App\Models\PasienPmo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PasienPmoFactory extends Factory
{
    protected $model = PasienPmo::class;

    public function definition(): array
    {
        return [
            'id_user'         => User::factory(),
            'pmo_user_id'     => User::factory(),
            'nama_pasien'     => $this->faker->name(),
            'nik'             => (string) $this->faker->numerify('################'),
            'nama_pmo'        => $this->faker->name(),
            'jenis_pmo'       => 'Keluarga',
            'tanggal_regis'   => now()->toDateString(),
            'status_diabetes' => 'Sedang',
            'is_active'       => true,
        ];
    }
}
```

- [ ] **Step 6: Buat `database/factories/JadwalMinumObatFactory.php`**

```php
<?php

namespace Database\Factories;

use App\Models\JadwalMinumObat;
use App\Models\MasterObat;
use App\Models\PasienPmo;
use Illuminate\Database\Eloquent\Factories\Factory;

class JadwalMinumObatFactory extends Factory
{
    protected $model = JadwalMinumObat::class;

    public function definition(): array
    {
        return [
            'id_pasien_pmo'      => PasienPmo::factory(),
            'obat_id'            => MasterObat::factory(),
            'nama_pasien'        => $this->faker->name(),
            'nama_pmo'           => $this->faker->name(),
            'tgl_mulai'          => now()->subDay()->toDateString(),
            'jam_mulai'          => '08:00:00',
            'frekuensi_per_hari' => 1,
            'status'             => 'aktif',
        ];
    }
}
```

- [ ] **Step 7: Jalankan test, pastikan LULUS**

Run: `php artisan test --filter=FactoryMoTest`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add database/factories/MasterObatFactory.php database/factories/PasienPmoFactory.php database/factories/JadwalMinumObatFactory.php app/Models/MasterObat.php tests/Unit/Pengingat/FactoryMoTest.php
git commit -m "test(pengingat): factory rantai MO (obat, pasien_pmo, jadwal)"
```

---

## Task 6: Repository `PengingatKejadianRepository`

**Files:**
- Create: `app/Repos/PengingatKejadianRepository.php`
- Test: `tests/Unit/Pengingat/PengingatKejadianRepositoryTest.php`

- [ ] **Step 1: Tulis test**

```php
<?php

namespace Tests\Unit\Pengingat;

use App\Models\PengingatKejadian;
use App\Models\User;
use App\Repos\PengingatKejadianRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PengingatKejadianRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function buatKejadian(): PengingatKejadian
    {
        $u = User::factory()->create();
        return PengingatKejadian::create([
            'jenis' => 'mo', 'jadwal_id' => $u->id, 'user_pasien_id' => $u->id,
            'waktu_jadwal' => Carbon::parse('2026-06-03 08:00:00'),
            'status' => PengingatKejadian::STATUS_MENUNGGU,
        ]);
    }

    public function test_first_or_create_idempoten(): void
    {
        $waktu = Carbon::parse('2026-06-03 08:00:00');
        $atr = ['user_pasien_id' => null, 'id_pasien_pmo' => null, 'user_pmo_id' => null];

        $a = PengingatKejadianRepository::firstOrCreateUntukSlot('mo', 'jadwal-1', $waktu, $atr);
        $b = PengingatKejadianRepository::firstOrCreateUntukSlot('mo', 'jadwal-1', $waktu, $atr);

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, PengingatKejadian::count());
    }

    public function test_tandai_dikirim_push_menaikkan_hitungan(): void
    {
        $k = $this->buatKejadian();
        $now = Carbon::parse('2026-06-03 08:05:00');

        PengingatKejadianRepository::tandaiDikirim($k, 'push', 'pasien', $now);

        $k->refresh();
        $this->assertSame(1, $k->jumlah_push);
        $this->assertTrue($k->terakhir_dikirim_pada->equalTo($now));
    }

    public function test_tandai_wa_pmo_set_eskalasi(): void
    {
        $k = $this->buatKejadian();
        PengingatKejadianRepository::tandaiDikirim($k, 'whatsapp', 'pmo', Carbon::now());

        $k->refresh();
        $this->assertSame(1, $k->jumlah_wa_pmo);
        $this->assertTrue($k->eskalasi_pmo);
    }

    public function test_tandai_terlewat_dan_dikonfirmasi(): void
    {
        $k = $this->buatKejadian();
        PengingatKejadianRepository::tandaiTerlewat($k);
        $this->assertSame(PengingatKejadian::STATUS_TERLEWAT, $k->refresh()->status);

        $k2 = $this->buatKejadian();
        $waktu = Carbon::parse('2026-06-03 08:10:00');
        PengingatKejadianRepository::tandaiDikonfirmasi($k2, 'log-1', $waktu);
        $k2->refresh();
        $this->assertSame(PengingatKejadian::STATUS_DIKONFIRMASI, $k2->status);
        $this->assertSame('log-1', $k2->konfirmasi_log_id);
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `php artisan test --filter=PengingatKejadianRepositoryTest`
Expected: FAIL.

- [ ] **Step 3: Buat `app/Repos/PengingatKejadianRepository.php`**

```php
<?php

namespace App\Repos;

use App\Models\PengingatKejadian;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PengingatKejadianRepository
{
    public static function firstOrCreateUntukSlot(string $jenis, string $jadwalId, Carbon $waktuJadwal, array $atribut): PengingatKejadian
    {
        return PengingatKejadian::firstOrCreate(
            ['jenis' => $jenis, 'jadwal_id' => $jadwalId, 'waktu_jadwal' => $waktuJadwal],
            array_merge($atribut, ['status' => PengingatKejadian::STATUS_MENUNGGU]),
        );
    }

    public static function menunggu(): Collection
    {
        return PengingatKejadian::menunggu()->orderBy('waktu_jadwal')->get();
    }

    public static function tandaiDikirim(PengingatKejadian $k, string $kanal, string $target, Carbon $waktu): void
    {
        DB::transaction(function () use ($k, $kanal, $target, $waktu) {
            if ($kanal === 'push') {
                $k->increment('jumlah_push');
            } elseif ($target === 'pmo') {
                $k->increment('jumlah_wa_pmo');
            } else {
                $k->increment('jumlah_wa_pasien');
            }

            $update = ['terakhir_dikirim_pada' => $waktu];
            if ($target === 'pmo') {
                $update['eskalasi_pmo'] = true;
            }
            $k->forceFill($update)->save();
        });
    }

    public static function tandaiTerlewat(PengingatKejadian $k): void
    {
        $k->forceFill(['status' => PengingatKejadian::STATUS_TERLEWAT])->save();
    }

    public static function tandaiDikonfirmasi(PengingatKejadian $k, string $logId, Carbon $waktu): void
    {
        $k->forceFill([
            'status'            => PengingatKejadian::STATUS_DIKONFIRMASI,
            'konfirmasi_log_id' => $logId,
            'dikonfirmasi_pada' => $waktu,
        ])->save();
    }
}
```

- [ ] **Step 4: Jalankan test, pastikan LULUS**

Run: `php artisan test --filter=PengingatKejadianRepositoryTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Repos/PengingatKejadianRepository.php tests/Unit/Pengingat/PengingatKejadianRepositoryTest.php
git commit -m "feat(pengingat): PengingatKejadianRepository (static, transaksional)"
```

---

## Task 7: Interface + `LogWhatsAppSender` + binding driver

**Files:**
- Create: `app/Services/Whatsapp/WhatsAppSender.php` (interface)
- Create: `app/Services/Whatsapp/LogWhatsAppSender.php`
- Create: `app/Services/Whatsapp/CloudApiWhatsAppSender.php` (stub diisi penuh di Task 8)
- Modify: `app/Providers/AppServiceProvider.php` (binding)
- Test: `tests/Unit/Pengingat/WhatsAppBindingTest.php`

- [ ] **Step 1: Tulis test**

```php
<?php

namespace Tests\Unit\Pengingat;

use App\Services\Whatsapp\CloudApiWhatsAppSender;
use App\Services\Whatsapp\LogWhatsAppSender;
use App\Services\Whatsapp\WhatsAppSender;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WhatsAppBindingTest extends TestCase
{
    public function test_driver_log_dipakai_secara_default(): void
    {
        config()->set('pengingat.whatsapp.driver', 'log');
        $this->assertInstanceOf(LogWhatsAppSender::class, app(WhatsAppSender::class));
    }

    public function test_driver_cloud_api_saat_dikonfigurasi(): void
    {
        config()->set('pengingat.whatsapp.driver', 'cloud_api');
        $this->assertInstanceOf(CloudApiWhatsAppSender::class, app(WhatsAppSender::class));
    }

    public function test_log_sender_menulis_log_dan_return_true(): void
    {
        Log::shouldReceive('info')->once();
        $ok = (new LogWhatsAppSender())->kirimTemplate('628123', 'pengingat_obat', ['a', 'b']);
        $this->assertTrue($ok);
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `php artisan test --filter=WhatsAppBindingTest`
Expected: FAIL.

- [ ] **Step 3: Buat interface `app/Services/Whatsapp/WhatsAppSender.php`**

```php
<?php

namespace App\Services\Whatsapp;

interface WhatsAppSender
{
    /**
     * Kirim WhatsApp berbasis template.
     * @param string $noHp Nomor format internasional tanpa '+' (mis. 628123...)
     * @param string $template Nama template terdaftar
     * @param array<int,string> $params Parameter berurutan untuk {{1}}, {{2}}, ...
     */
    public function kirimTemplate(string $noHp, string $template, array $params): bool;
}
```

- [ ] **Step 4: Buat `app/Services/Whatsapp/LogWhatsAppSender.php`**

```php
<?php

namespace App\Services\Whatsapp;

use Illuminate\Support\Facades\Log;

class LogWhatsAppSender implements WhatsAppSender
{
    public function kirimTemplate(string $noHp, string $template, array $params): bool
    {
        Log::info('[WA:log] kirim template', [
            'no'       => $noHp,
            'template' => $template,
            'params'   => $params,
        ]);

        return true;
    }
}
```

- [ ] **Step 5: Buat stub `app/Services/Whatsapp/CloudApiWhatsAppSender.php`**

```php
<?php

namespace App\Services\Whatsapp;

class CloudApiWhatsAppSender implements WhatsAppSender
{
    public function kirimTemplate(string $noHp, string $template, array $params): bool
    {
        // Diisi penuh di Task 8.
        return false;
    }
}
```

- [ ] **Step 6: Daftarkan binding di `app/Providers/AppServiceProvider.php`**

Di method `register()`, tambahkan:
```php
$this->app->bind(\App\Services\Whatsapp\WhatsAppSender::class, function () {
    return match (config('pengingat.whatsapp.driver')) {
        'cloud_api' => new \App\Services\Whatsapp\CloudApiWhatsAppSender(),
        default     => new \App\Services\Whatsapp\LogWhatsAppSender(),
    };
});
```

- [ ] **Step 7: Jalankan test, pastikan LULUS**

Run: `php artisan test --filter=WhatsAppBindingTest`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Services/Whatsapp/ app/Providers/AppServiceProvider.php tests/Unit/Pengingat/WhatsAppBindingTest.php
git commit -m "feat(pengingat): WhatsAppSender interface + LogWhatsAppSender + binding driver"
```

---

## Task 8: `CloudApiWhatsAppSender` (WhatsApp Cloud API)

**Files:**
- Modify: `app/Services/Whatsapp/CloudApiWhatsAppSender.php`
- Test: `tests/Unit/Pengingat/CloudApiWhatsAppSenderTest.php`

- [ ] **Step 1: Tulis test (Http::fake)**

```php
<?php

namespace Tests\Unit\Pengingat;

use App\Services\Whatsapp\CloudApiWhatsAppSender;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudApiWhatsAppSenderTest extends TestCase
{
    public function test_mengirim_payload_template_ke_graph_api(): void
    {
        config()->set('pengingat.whatsapp.cloud_api', [
            'token' => 'TOKEN', 'phone_id' => '123', 'lang' => 'id',
            'base_url' => 'https://graph.facebook.com/v21.0',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200),
        ]);

        $ok = (new CloudApiWhatsAppSender())->kirimTemplate('628123', 'pengingat_obat', ['Budi', 'Metformin', '08:00', 'https://x']);

        $this->assertTrue($ok);
        Http::assertSent(function ($request) {
            $body = $request->data();
            return str_contains($request->url(), '/123/messages')
                && $request->hasHeader('Authorization', 'Bearer TOKEN')
                && $body['type'] === 'template'
                && $body['template']['name'] === 'pengingat_obat'
                && $body['template']['components'][0]['parameters'][0]['text'] === 'Budi';
        });
    }

    public function test_return_false_saat_api_gagal(): void
    {
        config()->set('pengingat.whatsapp.cloud_api', [
            'token' => 'T', 'phone_id' => '1', 'lang' => 'id',
            'base_url' => 'https://graph.facebook.com/v21.0',
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => 'x'], 400)]);

        $ok = (new CloudApiWhatsAppSender())->kirimTemplate('628', 'pengingat_obat', ['a']);

        $this->assertFalse($ok);
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `php artisan test --filter=CloudApiWhatsAppSenderTest`
Expected: FAIL (stub return false untuk test pertama).

- [ ] **Step 3: Implementasi penuh `CloudApiWhatsAppSender`**

```php
<?php

namespace App\Services\Whatsapp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudApiWhatsAppSender implements WhatsAppSender
{
    public function kirimTemplate(string $noHp, string $template, array $params): bool
    {
        $cfg = config('pengingat.whatsapp.cloud_api');

        $parameters = array_map(
            fn ($text) => ['type' => 'text', 'text' => (string) $text],
            array_values($params),
        );

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $noHp,
            'type'              => 'template',
            'template'          => [
                'name'       => $template,
                'language'   => ['code' => $cfg['lang'] ?? 'id'],
                'components' => [
                    ['type' => 'body', 'parameters' => $parameters],
                ],
            ],
        ];

        $resp = Http::withToken($cfg['token'])
            ->acceptJson()
            ->post(rtrim($cfg['base_url'], '/') . '/' . $cfg['phone_id'] . '/messages', $payload);

        if (! $resp->successful()) {
            Log::warning('[WA:cloud_api] gagal', ['status' => $resp->status(), 'body' => $resp->body()]);
            return false;
        }

        return true;
    }
}
```

- [ ] **Step 4: Jalankan test, pastikan LULUS**

Run: `php artisan test --filter=CloudApiWhatsAppSenderTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Whatsapp/CloudApiWhatsAppSender.php tests/Unit/Pengingat/CloudApiWhatsAppSenderTest.php
git commit -m "feat(pengingat): CloudApiWhatsAppSender (WhatsApp Cloud API template)"
```

---

## Task 9: `WebPushSender` (kirim + prune subscription kedaluwarsa)

**Files:**
- Create: `app/Services/WebPush/WebPushSender.php`
- Test: `tests/Unit/Pengingat/WebPushSenderTest.php`

> Catatan testabilitas: pengiriman jaringan nyata (flush ke push service) diuji manual (butuh browser + push service). Yang diuji otomatis adalah logika **prune** subscription kedaluwarsa berdasar daftar endpoint, yang murni DB.

- [ ] **Step 1: Tulis test**

```php
<?php

namespace Tests\Unit\Pengingat;

use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPush\WebPushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebPushSenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_hapus_subscription_kedaluwarsa_berdasar_endpoint(): void
    {
        $u = User::factory()->create();
        PushSubscription::create(['user_id' => $u->id, 'endpoint' => 'https://a', 'public_key' => 'p', 'auth_token' => 'a']);
        PushSubscription::create(['user_id' => $u->id, 'endpoint' => 'https://b', 'public_key' => 'p', 'auth_token' => 'a']);

        (new WebPushSender())->hapusSubscriptionKedaluwarsa(['https://a']);

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => 'https://a']);
        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => 'https://b']);
    }

    public function test_kirim_ke_user_tanpa_subscription_tidak_error_return_nol(): void
    {
        $u = User::factory()->create();
        $terkirim = (new WebPushSender())->kirimKeUser($u->id, ['judul' => 'Hai', 'isi' => 'tes', 'url' => '/']);
        $this->assertSame(0, $terkirim);
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `php artisan test --filter=WebPushSenderTest`
Expected: FAIL.

- [ ] **Step 3: Buat `app/Services/WebPush/WebPushSender.php`**

```php
<?php

namespace App\Services\WebPush;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushSender
{
    private ?WebPush $webPush = null;

    private function client(): WebPush
    {
        if ($this->webPush === null) {
            $this->webPush = new WebPush([
                'VAPID' => [
                    'subject'    => config('pengingat.vapid.subject'),
                    'publicKey'  => config('pengingat.vapid.public_key'),
                    'privateKey' => config('pengingat.vapid.private_key'),
                ],
            ]);
        }

        return $this->webPush;
    }

    /**
     * Kirim payload ke seluruh subscription milik user.
     * @param array{judul:string,isi:string,url:string} $payload
     * @return int jumlah notifikasi yang dikirim (di-queue)
     */
    public function kirimKeUser(string $userId, array $payload): int
    {
        $subs = PushSubscription::where('user_id', $userId)->get();
        if ($subs->isEmpty()) {
            return 0;
        }

        $client = $this->client();
        $body = json_encode([
            'title' => $payload['judul'],
            'body'  => $payload['isi'],
            'url'   => $payload['url'],
        ]);

        foreach ($subs as $sub) {
            $client->queueNotification(
                Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'keys'     => ['p256dh' => $sub->public_key, 'auth' => $sub->auth_token],
                ]),
                $body,
            );
        }

        $endpointKedaluwarsa = [];
        foreach ($client->flush() as $report) {
            if (! $report->isSuccess() && $report->isSubscriptionExpired()) {
                $endpointKedaluwarsa[] = $report->getEndpoint();
            }
        }

        if ($endpointKedaluwarsa !== []) {
            $this->hapusSubscriptionKedaluwarsa($endpointKedaluwarsa);
        }

        return $subs->count();
    }

    public function hapusSubscriptionKedaluwarsa(array $endpoints): void
    {
        if ($endpoints === []) {
            return;
        }
        PushSubscription::whereIn('endpoint', $endpoints)->delete();
    }
}
```

- [ ] **Step 4: Jalankan test, pastikan LULUS**

Run: `php artisan test --filter=WebPushSenderTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/WebPush/WebPushSender.php tests/Unit/Pengingat/WebPushSenderTest.php
git commit -m "feat(pengingat): WebPushSender (kirim + prune subscription kedaluwarsa)"
```

---

## Task 10: `KirimPengingatJob`

**Files:**
- Create: `app/Jobs/KirimPengingatJob.php`
- Test: `tests/Feature/Pengingat/KirimPengingatJobTest.php`

> Job menerima `(kejadianId, kanal, target)`, menyusun pesan dari jadwal MO, memanggil sender, lalu mencatat `PengingatKirimLog`. WhatsApp diuji dengan binding fake sender; push diuji dengan mock `WebPushSender`.

- [ ] **Step 1: Tulis test**

```php
<?php

namespace Tests\Feature\Pengingat;

use App\Jobs\KirimPengingatJob;
use App\Models\JadwalMinumObat;
use App\Models\PengingatKejadian;
use App\Services\WebPush\WebPushSender;
use App\Services\Whatsapp\WhatsAppSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class KirimPengingatJobTest extends TestCase
{
    use RefreshDatabase;

    private function buatKejadian(): PengingatKejadian
    {
        $jadwal = JadwalMinumObat::factory()->create(['jam_mulai' => '08:00:00', 'frekuensi_per_hari' => 1]);
        $pp = $jadwal->pasienPmo;

        return PengingatKejadian::create([
            'jenis' => 'mo', 'jadwal_id' => $jadwal->id, 'id_pasien_pmo' => $pp->id,
            'user_pasien_id' => $pp->id_user, 'user_pmo_id' => $pp->pmo_user_id,
            'waktu_jadwal' => Carbon::parse(now()->toDateString() . ' 08:00:00'),
            'status' => PengingatKejadian::STATUS_MENUNGGU,
        ]);
    }

    public function test_kanal_whatsapp_memanggil_sender_dan_mencatat_log(): void
    {
        $k = $this->buatKejadian();
        // pasien punya nomor WA
        $k->pasien->update(['whatsapp_number' => '08123456789']);

        $fake = new class implements WhatsAppSender {
            public array $dipanggil = [];
            public function kirimTemplate(string $noHp, string $template, array $params): bool
            {
                $this->dipanggil[] = compact('noHp', 'template', 'params');
                return true;
            }
        };
        $this->app->instance(WhatsAppSender::class, $fake);

        (new KirimPengingatJob($k->id, 'whatsapp', 'pasien'))->handle(
            app(WhatsAppSender::class), app(WebPushSender::class)
        );

        $this->assertCount(1, $fake->dipanggil);
        $this->assertSame('628123456789', $fake->dipanggil[0]['noHp']); // dinormalkan 0 -> 62
        $this->assertDatabaseHas('pengingat_kirim_log', [
            'kejadian_id' => $k->id, 'kanal' => 'whatsapp', 'target' => 'pasien', 'status' => 'terkirim',
        ]);
    }

    public function test_kanal_push_memanggil_webpush_sender(): void
    {
        $k = $this->buatKejadian();

        $mock = $this->mock(WebPushSender::class);
        $mock->shouldReceive('kirimKeUser')->once()->andReturn(1);

        (new KirimPengingatJob($k->id, 'push', 'pasien'))->handle(
            app(WhatsAppSender::class), $mock
        );

        $this->assertDatabaseHas('pengingat_kirim_log', [
            'kejadian_id' => $k->id, 'kanal' => 'push', 'status' => 'terkirim',
        ]);
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `php artisan test --filter=KirimPengingatJobTest`
Expected: FAIL.

- [ ] **Step 3: Buat `app/Jobs/KirimPengingatJob.php`**

```php
<?php

namespace App\Jobs;

use App\Models\JadwalMinumObat;
use App\Models\PengingatKejadian;
use App\Models\PengingatKirimLog;
use App\Models\User;
use App\Services\WebPush\WebPushSender;
use App\Services\Whatsapp\WhatsAppSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class KirimPengingatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120];

    public function __construct(
        public string $kejadianId,
        public string $kanal,   // 'push' | 'whatsapp'
        public string $target,  // 'pasien' | 'pmo'
    ) {}

    public function handle(WhatsAppSender $wa, WebPushSender $push): void
    {
        $kejadian = PengingatKejadian::find($this->kejadianId);
        if (! $kejadian || $kejadian->status !== PengingatKejadian::STATUS_MENUNGGU) {
            return; // sudah dikonfirmasi / terlewat → jangan kirim
        }

        $jadwal = JadwalMinumObat::with('obat')->find($kejadian->jadwal_id);
        if (! $jadwal) {
            return;
        }

        $userId = $this->target === 'pmo' ? $kejadian->user_pmo_id : $kejadian->user_pasien_id;
        $user = $userId ? User::find($userId) : null;
        if (! $user) {
            return;
        }

        $namaObat = $jadwal->obat?->nama ?? 'obat';
        $jamSlot  = $kejadian->waktu_jadwal->format('H:i');
        $url      = url("/pengingat/{$kejadian->id}/konfirmasi"); // route dibuat penuh di Task 16

        try {
            if ($this->kanal === 'push') {
                $judul = $this->target === 'pmo' ? 'Pasien Anda belum minum obat' : 'Waktunya minum obat';
                $isi   = "Obat {$namaObat} jadwal jam {$jamSlot}.";
                $push->kirimKeUser($user->id, ['judul' => $judul, 'isi' => $isi, 'url' => $url]);
            } else {
                $no = $this->normalkanNomor($user->whatsapp_number);
                if (! $no) {
                    $this->catat($kejadian->id, 'gagal', 'nomor WA kosong');
                    return;
                }
                $template = config('pengingat.whatsapp.cloud_api.template_mo', 'pengingat_obat');
                $namaTujuan = $this->target === 'pmo' ? ($jadwal->nama_pmo ?? 'PMO') : ($jadwal->nama_pasien ?? 'Pasien');
                $wa->kirimTemplate($no, $template, [$namaTujuan, $namaObat, $jamSlot, $url]);
            }

            $this->catat($kejadian->id, 'terkirim', null);
        } catch (\Throwable $e) {
            $this->catat($kejadian->id, 'gagal', $e->getMessage());
            throw $e; // biar retry mekanisme antrian jalan
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[pengingat] KirimPengingatJob gagal total', [
            'kejadian' => $this->kejadianId, 'kanal' => $this->kanal, 'error' => $e->getMessage(),
        ]);
        $this->catat($this->kejadianId, 'gagal', $e->getMessage());
    }

    private function catat(string $kejadianId, string $status, ?string $error): void
    {
        PengingatKirimLog::create([
            'kejadian_id' => $kejadianId,
            'kanal'       => $this->kanal,
            'target'      => $this->target,
            'status'      => $status,
            'error'       => $error,
        ]);
    }

    private function normalkanNomor(?string $no): ?string
    {
        if (blank($no)) {
            return null;
        }
        $no = preg_replace('/\D+/', '', $no);
        if (str_starts_with($no, '0')) {
            $no = '62' . substr($no, 1);
        } elseif (! str_starts_with($no, '62')) {
            $no = '62' . $no;
        }
        return $no;
    }
}
```

- [ ] **Step 4: Jalankan test, pastikan LULUS**

Run: `php artisan test --filter=KirimPengingatJobTest`
Expected: PASS. (Job memakai `url()` langsung, tidak bergantung named route — jadi mandiri.)

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/KirimPengingatJob.php tests/Feature/Pengingat/KirimPengingatJobTest.php
git commit -m "feat(pengingat): KirimPengingatJob (push/WA, normalisasi nomor, log kirim)"
```

---

## Task 11: `PengingatTickService` — keputusan eskalasi (fase 2, pure)

**Files:**
- Create: `app/Services/PengingatTickService.php` (method `tentukanAksi` + konstanta)
- Test: `tests/Unit/Pengingat/TentukanAksiTest.php`

> Method `tentukanAksi(PengingatKejadian $k, Carbon $now): array` murni (tanpa side-effect) → mudah diuji. Mengembalikan `['keputusan' => 'skip'|'terlewat'|'kirim', 'aksi' => array<int,array{kanal:string,target:string}>]`.

- [ ] **Step 1: Tulis test**

```php
<?php

namespace Tests\Unit\Pengingat;

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
        PushSubscription::create(['user_id' => $userId, 'endpoint' => 'https://e/' . $userId, 'public_key' => 'p', 'auth_token' => 'a']);
    }

    public function test_punya_push_menit_0_kirim_push_pasien(): void
    {
        $k = $this->kejadian();
        $this->beriPush($k->user_pasien_id);

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(0));

        $this->assertSame('kirim', $hasil['keputusan']);
        $this->assertSame([['kanal' => 'push', 'target' => 'pasien']], $hasil['aksi']);
    }

    public function test_tanpa_push_menit_0_kirim_wa_pasien(): void
    {
        $k = $this->kejadian();

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(0));

        $this->assertSame([['kanal' => 'whatsapp', 'target' => 'pasien']], $hasil['aksi']);
    }

    public function test_skip_bila_belum_lewat_interval_ulang(): void
    {
        $k = $this->kejadian(['terakhir_dikirim_pada' => $this->jadwal->copy()->addMinutes(2)]);
        $this->beriPush($k->user_pasien_id);

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(5));

        $this->assertSame('skip', $hasil['keputusan']);
        $this->assertSame([], $hasil['aksi']);
    }

    public function test_menit_30_punya_push_kirim_push_dan_wa_pasien(): void
    {
        $k = $this->kejadian();
        $this->beriPush($k->user_pasien_id);

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(30));

        $this->assertContains(['kanal' => 'push', 'target' => 'pasien'], $hasil['aksi']);
        $this->assertContains(['kanal' => 'whatsapp', 'target' => 'pasien'], $hasil['aksi']);
    }

    public function test_menit_60_eskalasi_ke_pmo(): void
    {
        $k = $this->kejadian();

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(60));

        $this->assertContains(['kanal' => 'whatsapp', 'target' => 'pmo'], $hasil['aksi']);
    }

    public function test_menit_60_tidak_eskalasi_dua_kali(): void
    {
        $k = $this->kejadian(['eskalasi_pmo' => true, 'terakhir_dikirim_pada' => $this->jadwal->copy()->addMinutes(45)]);

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(60));

        $targets = array_column($hasil['aksi'], 'target');
        $this->assertNotContains('pmo', $targets);
    }

    public function test_lewat_batas_akhir_terlewat(): void
    {
        $k = $this->kejadian();

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(121));

        $this->assertSame('terlewat', $hasil['keputusan']);
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `php artisan test --filter=TentukanAksiTest`
Expected: FAIL.

- [ ] **Step 3: Buat `app/Services/PengingatTickService.php` (parsial — `tentukanAksi`)**

```php
<?php

namespace App\Services;

use App\Models\PengingatKejadian;
use App\Models\PushSubscription;
use Illuminate\Support\Carbon;

class PengingatTickService
{
    /**
     * Keputusan murni untuk satu kejadian (tanpa side-effect).
     * @return array{keputusan:string,aksi:array<int,array{kanal:string,target:string}>}
     */
    public static function tentukanAksi(PengingatKejadian $k, Carbon $now): array
    {
        $batasAkhir   = (int) config('pengingat.batas_akhir_menit');
        $intervalUlang = (int) config('pengingat.interval_ulang_menit');
        $waPasienMnt  = (int) config('pengingat.wa_pasien_setelah_menit');
        $waPmoMnt     = (int) config('pengingat.wa_pmo_setelah_menit');

        $selisih = intdiv($now->getTimestamp() - $k->waktu_jadwal->getTimestamp(), 60);

        if ($selisih > $batasAkhir) {
            return ['keputusan' => 'terlewat', 'aksi' => []];
        }

        if ($k->terakhir_dikirim_pada) {
            $sejakTerakhir = intdiv($now->getTimestamp() - $k->terakhir_dikirim_pada->getTimestamp(), 60);
            if ($sejakTerakhir < $intervalUlang) {
                return ['keputusan' => 'skip', 'aksi' => []];
            }
        }

        $pasienPunyaPush = PushSubscription::where('user_id', $k->user_pasien_id)->exists();
        $pmoPunyaPush    = $k->user_pmo_id && PushSubscription::where('user_id', $k->user_pmo_id)->exists();

        $aksi = [];

        // --- Kanal pasien ---
        if ($selisih < $waPasienMnt) {
            // Sebelum ambang WA: push bila ada, kalau tidak ada push → WA sejak menit-0.
            $aksi[] = $pasienPunyaPush
                ? ['kanal' => 'push', 'target' => 'pasien']
                : ['kanal' => 'whatsapp', 'target' => 'pasien'];
        } else {
            // Sudah lewat ambang WA: kirim WA; push tetap diulang bila ada.
            if ($pasienPunyaPush) {
                $aksi[] = ['kanal' => 'push', 'target' => 'pasien'];
            }
            $aksi[] = ['kanal' => 'whatsapp', 'target' => 'pasien'];
        }

        // --- Eskalasi PMO ---
        if ($selisih >= $waPmoMnt && ! $k->eskalasi_pmo && $k->user_pmo_id) {
            $aksi[] = ['kanal' => 'whatsapp', 'target' => 'pmo'];
            if ($pmoPunyaPush) {
                $aksi[] = ['kanal' => 'push', 'target' => 'pmo'];
            }
        }

        return ['keputusan' => 'kirim', 'aksi' => $aksi];
    }
}
```

- [ ] **Step 4: Jalankan test, pastikan LULUS**

Run: `php artisan test --filter=TentukanAksiTest`
Expected: PASS (7 test).

- [ ] **Step 5: Commit**

```bash
git add app/Services/PengingatTickService.php tests/Unit/Pengingat/TentukanAksiTest.php
git commit -m "feat(pengingat): logika keputusan eskalasi (tentukanAksi)"
```

---

## Task 12: `PengingatTickService` — materialisasi & proses (fase 1 & 2)

**Files:**
- Modify: `app/Services/PengingatTickService.php` (tambah `jalankan`, `materialisasiMo`, `proses`)
- Test: `tests/Feature/Pengingat/PengingatTickServiceTest.php`

- [ ] **Step 1: Tulis test**

```php
<?php

namespace Tests\Feature\Pengingat;

use App\Jobs\KirimPengingatJob;
use App\Models\JadwalMinumObat;
use App\Models\PengingatKejadian;
use App\Services\PengingatTickService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PengingatTickServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_materialisasi_membuat_kejadian_untuk_slot_yang_lewat(): void
    {
        Carbon::setTestNow(Carbon::parse(now()->toDateString() . ' 08:05:00'));
        JadwalMinumObat::factory()->create(['jam_mulai' => '08:00:00', 'frekuensi_per_hari' => 1, 'tgl_mulai' => now()->subDay()->toDateString()]);

        PengingatTickService::materialisasiMo();

        $this->assertSame(1, PengingatKejadian::count());
        $this->assertSame('menunggu', PengingatKejadian::first()->status);
    }

    public function test_materialisasi_idempoten_walau_dipanggil_dua_kali(): void
    {
        Carbon::setTestNow(Carbon::parse(now()->toDateString() . ' 08:05:00'));
        JadwalMinumObat::factory()->create(['jam_mulai' => '08:00:00', 'frekuensi_per_hari' => 1, 'tgl_mulai' => now()->subDay()->toDateString()]);

        PengingatTickService::materialisasiMo();
        PengingatTickService::materialisasiMo();

        $this->assertSame(1, PengingatKejadian::count());
    }

    public function test_materialisasi_abaikan_slot_belum_tiba(): void
    {
        Carbon::setTestNow(Carbon::parse(now()->toDateString() . ' 07:00:00'));
        JadwalMinumObat::factory()->create(['jam_mulai' => '08:00:00', 'frekuensi_per_hari' => 1, 'tgl_mulai' => now()->subDay()->toDateString()]);

        PengingatTickService::materialisasiMo();

        $this->assertSame(0, PengingatKejadian::count());
    }

    public function test_proses_mendispatch_job_dan_set_terlewat(): void
    {
        Queue::fake();
        $jadwal = JadwalMinumObat::factory()->create(['jam_mulai' => '08:00:00', 'frekuensi_per_hari' => 1]);
        $pp = $jadwal->pasienPmo;

        // kejadian baru (menit 0) → harus dispatch
        $baru = PengingatKejadian::create([
            'jenis' => 'mo', 'jadwal_id' => $jadwal->id, 'id_pasien_pmo' => $pp->id,
            'user_pasien_id' => $pp->id_user, 'user_pmo_id' => $pp->pmo_user_id,
            'waktu_jadwal' => now()->copy()->subMinutes(1), 'status' => PengingatKejadian::STATUS_MENUNGGU,
        ]);
        // kejadian sudah lewat batas → harus jadi terlewat, tanpa dispatch
        $lama = PengingatKejadian::create([
            'jenis' => 'mo', 'jadwal_id' => $jadwal->id, 'id_pasien_pmo' => $pp->id,
            'user_pasien_id' => $pp->id_user, 'user_pmo_id' => $pp->pmo_user_id,
            'waktu_jadwal' => now()->copy()->subMinutes(200), 'status' => PengingatKejadian::STATUS_MENUNGGU,
        ]);

        PengingatTickService::proses();

        Queue::assertPushed(KirimPengingatJob::class, 1);
        $this->assertSame('terlewat', $lama->refresh()->status);
        $this->assertSame('menunggu', $baru->refresh()->status);
        $this->assertNotNull($baru->refresh()->terakhir_dikirim_pada);
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `php artisan test --filter=PengingatTickServiceTest`
Expected: FAIL (method belum ada).

- [ ] **Step 3: Tambah method ke `PengingatTickService`**

Tambahkan `use` di atas kelas:
```php
use App\Jobs\KirimPengingatJob;
use App\Models\JadwalMinumObat;
use App\Repos\PengingatKejadianRepository;
use Illuminate\Support\Facades\Log;
```
Tambahkan method di dalam kelas (di bawah `tentukanAksi`):
```php
    public static function jalankan(): void
    {
        if (config('pengingat.aktif.mo')) {
            self::materialisasiMo();
        }
        self::proses();
    }

    public static function materialisasiMo(): void
    {
        $now    = Carbon::now();
        $hariIni = $now->toDateString();
        $batas  = (int) config('pengingat.batas_akhir_menit');

        JadwalMinumObat::query()->active()
            ->with('pasienPmo')
            ->whereDate('tgl_mulai', '<=', $hariIni)
            ->chunk(200, function ($jadwals) use ($now, $hariIni, $batas) {
                foreach ($jadwals as $jadwal) {
                    $pp = $jadwal->pasienPmo;
                    foreach ($jadwal->slot_jam_harian as $slot) {
                        $waktu = Carbon::parse($hariIni . ' ' . $slot . ':00');

                        // hanya slot yang sudah tiba & masih dalam batas akhir
                        $selisih = intdiv($now->getTimestamp() - $waktu->getTimestamp(), 60);
                        if ($selisih < 0 || $selisih > $batas) {
                            continue;
                        }

                        PengingatKejadianRepository::firstOrCreateUntukSlot('mo', $jadwal->id, $waktu, [
                            'id_pasien_pmo'  => $jadwal->id_pasien_pmo,
                            'user_pasien_id' => $pp?->id_user,
                            'user_pmo_id'    => $pp?->pmo_user_id,
                        ]);
                    }
                }
            });
    }

    public static function proses(): void
    {
        $now = Carbon::now();

        foreach (PengingatKejadianRepository::menunggu() as $k) {
            try {
                $hasil = self::tentukanAksi($k, $now);

                if ($hasil['keputusan'] === 'terlewat') {
                    PengingatKejadianRepository::tandaiTerlewat($k);
                    continue;
                }
                if ($hasil['keputusan'] === 'skip' || $hasil['aksi'] === []) {
                    continue;
                }

                foreach ($hasil['aksi'] as $aksi) {
                    KirimPengingatJob::dispatch($k->id, $aksi['kanal'], $aksi['target']);
                    PengingatKejadianRepository::tandaiDikirim($k, $aksi['kanal'], $aksi['target'], $now);
                }
            } catch (\Throwable $e) {
                Log::error('[pengingat] gagal memproses kejadian', ['id' => $k->id, 'error' => $e->getMessage()]);
            }
        }
    }
```

- [ ] **Step 4: Jalankan test, pastikan LULUS**

Run: `php artisan test --filter=PengingatTickServiceTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/PengingatTickService.php tests/Feature/Pengingat/PengingatTickServiceTest.php
git commit -m "feat(pengingat): materialisasi MO + proses (dispatch job, terlewat)"
```

---

## Task 13: Command `pengingat:tick` + jadwalkan tiap menit

**Files:**
- Create: `app/Console/Commands/PengingatTick.php`
- Modify: `routes/console.php`
- Test: `tests/Feature/Pengingat/PengingatTickCommandTest.php`

> Route konfirmasi dibuat penuh di Task 16. `KirimPengingatJob` memakai `url()` langsung, jadi command/job tidak butuh named route di tahap ini.

- [ ] **Step 1: Tulis test**

```php
<?php

namespace Tests\Feature\Pengingat;

use App\Jobs\KirimPengingatJob;
use App\Models\JadwalMinumObat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PengingatTickCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_command_materialisasi_dan_dispatch(): void
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::parse(now()->toDateString() . ' 08:01:00'));
        JadwalMinumObat::factory()->create(['jam_mulai' => '08:00:00', 'frekuensi_per_hari' => 1, 'tgl_mulai' => now()->subDay()->toDateString()]);

        $this->artisan('pengingat:tick')->assertExitCode(0);

        Queue::assertPushed(KirimPengingatJob::class);
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `php artisan test --filter=PengingatTickCommandTest`
Expected: FAIL.

- [ ] **Step 3: Buat `app/Console/Commands/PengingatTick.php`**

```php
<?php

namespace App\Console\Commands;

use App\Services\PengingatTickService;
use Illuminate\Console\Command;

class PengingatTick extends Command
{
    protected $signature = 'pengingat:tick';

    protected $description = 'Materialisasi & proses pengingat MO yang jatuh tempo (jalan tiap menit)';

    public function handle(): int
    {
        PengingatTickService::jalankan();
        $this->info('pengingat:tick selesai pada ' . now()->format('Y-m-d H:i:s'));

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Jadwalkan di `routes/console.php`**

Tambahkan import di atas & jadwal di bawah:
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('pengingat:tick')->everyMinute()->withoutOverlapping();
```

- [ ] **Step 5: Jalankan test command, pastikan LULUS**

Run: `php artisan test --filter=PengingatTickCommandTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Console/Commands/PengingatTick.php routes/console.php tests/Feature/Pengingat/PengingatTickCommandTest.php
git commit -m "feat(pengingat): command pengingat:tick + schedule everyMinute"
```

---

## Task 14: Endpoint langganan Web Push

**Files:**
- Create: `app/Http/Controllers/PushSubscriptionController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Pengingat/PushSubscriptionEndpointTest.php`

- [ ] **Step 1: Tulis test**

```php
<?php

namespace Tests\Feature\Pengingat;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_login_bisa_subscribe(): void
    {
        $user = User::factory()->create();

        $resp = $this->actingAs($user)->postJson('/push/subscribe', [
            'endpoint' => 'https://push.example/xyz',
            'keys'     => ['p256dh' => 'kunci-publik', 'auth' => 'kunci-auth'],
        ]);

        $resp->assertOk();
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id, 'endpoint' => 'https://push.example/xyz',
        ]);
    }

    public function test_subscribe_idempoten_per_endpoint(): void
    {
        $user = User::factory()->create();
        $payload = ['endpoint' => 'https://push.example/xyz', 'keys' => ['p256dh' => 'a', 'auth' => 'b']];

        $this->actingAs($user)->postJson('/push/subscribe', $payload)->assertOk();
        $this->actingAs($user)->postJson('/push/subscribe', $payload)->assertOk();

        $this->assertSame(1, \App\Models\PushSubscription::where('endpoint', 'https://push.example/xyz')->count());
    }

    public function test_bisa_unsubscribe(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/push/subscribe', [
            'endpoint' => 'https://push.example/xyz', 'keys' => ['p256dh' => 'a', 'auth' => 'b'],
        ])->assertOk();

        $this->actingAs($user)->deleteJson('/push/unsubscribe', ['endpoint' => 'https://push.example/xyz'])->assertOk();

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => 'https://push.example/xyz']);
    }

    public function test_guest_ditolak(): void
    {
        $this->postJson('/push/subscribe', [])->assertUnauthorized();
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `php artisan test --filter=PushSubscriptionEndpointTest`
Expected: FAIL.

- [ ] **Step 3: Buat `app/Http/Controllers/PushSubscriptionController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint'     => ['required', 'string'],
            'keys.p256dh'  => ['required', 'string'],
            'keys.auth'    => ['required', 'string'],
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id'    => $request->user()->id,
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'user_agent' => $request->userAgent(),
            ],
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'string']]);

        PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint', $data['endpoint'])
            ->delete();

        return response()->json(['ok' => true]);
    }
}
```

- [ ] **Step 4: Daftarkan route di `routes/web.php`**

Di dalam grup `['auth','verified']`, tambahkan:
```php
Route::post('/push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'store'])->name('push.subscribe');
Route::delete('/push/unsubscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
```

- [ ] **Step 5: Jalankan test, pastikan LULUS**

Run: `php artisan test --filter=PushSubscriptionEndpointTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/PushSubscriptionController.php routes/web.php tests/Feature/Pengingat/PushSubscriptionEndpointTest.php
git commit -m "feat(pengingat): endpoint subscribe/unsubscribe Web Push"
```

---

## Task 15: Service Worker + registrasi push (frontend)

**Files:**
- Create: `public/sw.js`
- Create: `resources/js/pengingat-push.js`
- Modify: `resources/js/app.js` (import)
- Modify: `resources/views/layouts/app.blade.php` (meta VAPID + tombol)
- Test: manual (browser) — tidak ada test otomatis (memerlukan push service nyata)

> Generate kunci VAPID dulu agar `.env` terisi. Library minishlink menyediakan generator. Jalankan tinker:

- [ ] **Step 1: Generate kunci VAPID & isi `.env`**

Run:
```bash
php artisan tinker --execute="dump(Minishlink\WebPush\VAPID::createVapidKeys());"
```
Salin `publicKey` → `VAPID_PUBLIC_KEY`, `privateKey` → `VAPID_PRIVATE_KEY` di `.env`. Lalu `php artisan config:clear`.

- [ ] **Step 2: Buat `public/sw.js`**

```js
self.addEventListener('push', function (event) {
  let data = {};
  try { data = event.data ? event.data.json() : {}; } catch (e) { data = {}; }
  const title = data.title || 'Pengingat Kesehatan';
  const options = {
    body: data.body || '',
    icon: '/favicon.ico',
    badge: '/favicon.ico',
    data: { url: data.url || '/' },
  };
  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  const url = (event.notification.data && event.notification.data.url) || '/';
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (list) {
      for (const c of list) {
        if (c.url.includes(url) && 'focus' in c) return c.focus();
      }
      if (clients.openWindow) return clients.openWindow(url);
    })
  );
});
```

- [ ] **Step 3: Buat `resources/js/pengingat-push.js`**

```js
function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const raw = window.atob(base64);
  return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
}

async function aktifkanPengingat() {
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    alert('Browser ini tidak mendukung notifikasi push. Anda tetap akan diingatkan via WhatsApp.');
    return;
  }

  const vapidPublic = document.querySelector('meta[name="vapid-public-key"]')?.content;
  if (!vapidPublic) return;

  const izin = await Notification.requestPermission();
  if (izin !== 'granted') return;

  const reg = await navigator.serviceWorker.register('/sw.js');
  const sub = await reg.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey: urlBase64ToUint8Array(vapidPublic),
  });

  const json = sub.toJSON();
  await window.axios.post('/push/subscribe', {
    endpoint: json.endpoint,
    keys: { p256dh: json.keys.p256dh, auth: json.keys.auth },
  });

  alert('Pengingat via notifikasi telah diaktifkan.');
}

window.whenKesehatanReady &&
  window.whenKesehatanReady(() => {
    const btn = document.getElementById('btn-aktifkan-pengingat');
    if (btn) btn.addEventListener('click', aktifkanPengingat);
  });
```

- [ ] **Step 4: Import di `resources/js/app.js`**

Tambahkan baris import (sesuaikan dengan gaya import yang ada di file):
```js
import './pengingat-push.js';
```

- [ ] **Step 5: Tambah meta VAPID & tombol di `resources/views/layouts/app.blade.php`**

Di dalam `<head>` (dekat meta CSRF):
```blade
    <meta name="vapid-public-key" content="{{ config('pengingat.vapid.public_key') }}">
```
Tombol (letakkan di area dashboard pasien, mis. dalam `resources/views/dashboard` atau header untuk role pasien):
```blade
@auth
    @if(auth()->user()->isPasien())
        <button id="btn-aktifkan-pengingat" type="button" class="btn btn-sm btn-primary">
            <i class="ri-notification-3-line"></i> Aktifkan Pengingat
        </button>
    @endif
@endauth
```

- [ ] **Step 6: Build & verifikasi manual**

Run:
```bash
npm run build
```
Manual (butuh HTTPS atau `localhost`): login sebagai pasien → klik "Aktifkan Pengingat" → izinkan notifikasi → cek baris baru di `push_subscriptions`. Buat jadwal MO dengan slot menit berikutnya, jalankan `php artisan queue:work` + `php artisan pengingat:tick`, pastikan notifikasi muncul walau tab ditutup.

- [ ] **Step 7: Commit**

```bash
git add public/sw.js resources/js/pengingat-push.js resources/js/app.js resources/views/layouts/app.blade.php
git commit -m "feat(pengingat): service worker + registrasi push + tombol aktifkan"
```

---

## Task 16: Halaman konfirmasi (tutup loop eskalasi)

**Files:**
- Create: `app/Http/Controllers/KonfirmasiPengingatController.php`
- Create: `resources/views/pengingat/konfirmasi.blade.php`
- Modify: `routes/web.php` (ganti closure placeholder Task 13 + tambah POST)
- Test: `tests/Feature/Pengingat/KonfirmasiPengingatTest.php`

> Konfirmasi membuat `PengingatMoLog` (foto WAJIB sesuai skema) lalu set kejadian `dikonfirmasi`. Tick berhenti otomatis karena `proses()` hanya membaca status `menunggu` dan job mengecek status sebelum kirim.

- [ ] **Step 1: Tulis test**

```php
<?php

namespace Tests\Feature\Pengingat;

use App\Models\JadwalMinumObat;
use App\Models\PengingatKejadian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KonfirmasiPengingatTest extends TestCase
{
    use RefreshDatabase;

    private function buatKejadianUntukPasien(User $pasien): PengingatKejadian
    {
        $jadwal = JadwalMinumObat::factory()->create(['jam_mulai' => '08:00:00', 'frekuensi_per_hari' => 1]);
        $pp = $jadwal->pasienPmo;
        $pp->update(['id_user' => $pasien->id]);

        return PengingatKejadian::create([
            'jenis' => 'mo', 'jadwal_id' => $jadwal->id, 'id_pasien_pmo' => $pp->id,
            'user_pasien_id' => $pasien->id, 'user_pmo_id' => $pp->pmo_user_id,
            'waktu_jadwal' => Carbon::parse(now()->toDateString() . ' 08:00:00'),
            'status' => PengingatKejadian::STATUS_MENUNGGU,
        ]);
    }

    public function test_pasien_konfirmasi_membuat_log_dan_menutup_kejadian(): void
    {
        Storage::fake('public');
        $pasien = User::factory()->create(['role' => 'pasien']);
        $k = $this->buatKejadianUntukPasien($pasien);

        $resp = $this->actingAs($pasien)->post(route('pengingat.konfirmasi.store', $k->id), [
            'foto_obat' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

        $resp->assertRedirect();
        $k->refresh();
        $this->assertSame(PengingatKejadian::STATUS_DIKONFIRMASI, $k->status);
        $this->assertNotNull($k->konfirmasi_log_id);
        $this->assertDatabaseHas('pengingat_mo_logs', ['id' => $k->konfirmasi_log_id, 'id_jo' => $k->jadwal_id]);
    }

    public function test_user_lain_tidak_boleh_konfirmasi(): void
    {
        $pasien = User::factory()->create(['role' => 'pasien']);
        $orangLain = User::factory()->create(['role' => 'pasien']);
        $k = $this->buatKejadianUntukPasien($pasien);

        $this->actingAs($orangLain)
            ->post(route('pengingat.konfirmasi.store', $k->id), ['foto_obat' => UploadedFile::fake()->image('x.jpg')])
            ->assertForbidden();
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `php artisan test --filter=KonfirmasiPengingatTest`
Expected: FAIL.

- [ ] **Step 3: Buat `app/Http/Controllers/KonfirmasiPengingatController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Models\JadwalMinumObat;
use App\Models\PengingatKejadian;
use App\Models\PengingatMoLog;
use App\Repos\PengingatKejadianRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KonfirmasiPengingatController extends Controller
{
    public function show(PengingatKejadian $kejadian)
    {
        $this->pastikanBerhak($kejadian);
        $jadwal = JadwalMinumObat::with('obat')->findOrFail($kejadian->jadwal_id);

        return view('pengingat.konfirmasi', [
            'kejadian' => $kejadian,
            'jadwal'   => $jadwal,
            'namaObat' => $jadwal->obat?->nama ?? 'Obat',
            'jamSlot'  => $kejadian->waktu_jadwal->format('H:i'),
        ]);
    }

    public function store(Request $request, PengingatKejadian $kejadian)
    {
        $this->pastikanBerhak($kejadian);

        $request->validate([
            'foto_obat' => ['required', 'image', 'max:5120'],
        ]);

        if ($kejadian->status !== PengingatKejadian::STATUS_MENUNGGU) {
            return redirect()->route($request->user()->homeRoute())
                ->with('info', 'Pengingat ini sudah ditindaklanjuti.');
        }

        $jadwal = JadwalMinumObat::with('obat')->findOrFail($kejadian->jadwal_id);
        $now    = now();
        $jamSlot = $kejadian->waktu_jadwal->format('H:i');
        $jamKini = $now->format('H:i');

        $path = $request->file('foto_obat')->store('pengingat-mo', 'public');

        $log = PengingatMoLog::create([
            'id_jo'           => $jadwal->id,
            'id_user'         => $kejadian->user_pasien_id,
            'nama_pasien'     => $jadwal->nama_pasien,
            'nama_obat'       => $jadwal->obat?->nama,
            'tgl_minum_obat'  => $now->toDateString(),
            'jam_minum_obat'  => $now->format('H:i:s'),
            'jam_slot_target' => $kejadian->waktu_jadwal->format('H:i:s'),
            'patuh_menit'     => PengingatMoLog::calculatePatuhMenit($jamSlot, $jamKini),
            'foto_obat'       => $path,
            'status'          => 'aktif',
            'created_by'      => Auth::id(),
        ]);

        PengingatKejadianRepository::tandaiDikonfirmasi($kejadian, $log->id, $now);

        return redirect()->route($request->user()->homeRoute())
            ->with('success', 'Terima kasih, konfirmasi minum obat tersimpan.');
    }

    private function pastikanBerhak(PengingatKejadian $kejadian): void
    {
        $uid = Auth::id();
        if ($uid !== $kejadian->user_pasien_id && $uid !== $kejadian->user_pmo_id) {
            abort(403);
        }
    }
}
```

- [ ] **Step 4: Buat view `resources/views/pengingat/konfirmasi.blade.php`**

```blade
@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 480px;">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Konfirmasi Minum Obat</h5>
            <p class="mb-1"><strong>{{ $namaObat }}</strong></p>
            <p class="text-muted">Jadwal jam {{ $jamSlot }}</p>

            @if($kejadian->status !== \App\Models\PengingatKejadian::STATUS_MENUNGGU)
                <div class="alert alert-info">Pengingat ini sudah ditindaklanjuti.</div>
            @else
                <form method="POST" action="{{ route('pengingat.konfirmasi.store', $kejadian->id) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Foto bukti minum obat</label>
                        <input type="file" name="foto_obat" accept="image/*" capture="environment" class="form-control @error('foto_obat') is-invalid @enderror" required>
                        @error('foto_obat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="ri-check-line"></i> Sudah Minum Obat
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
```

- [ ] **Step 5: Tambah route konfirmasi (GET + POST) di `routes/web.php`**

Di dalam grup `['auth','verified']`, tambahkan:
```php
Route::get('/pengingat/{kejadian}/konfirmasi', [\App\Http\Controllers\KonfirmasiPengingatController::class, 'show'])
    ->name('pengingat.konfirmasi.show')->where('kejadian', '[0-9a-f\-]+');
Route::post('/pengingat/{kejadian}/konfirmasi', [\App\Http\Controllers\KonfirmasiPengingatController::class, 'store'])
    ->name('pengingat.konfirmasi.store')->where('kejadian', '[0-9a-f\-]+');
```

- [ ] **Step 6: Jalankan test, pastikan LULUS**

Run: `php artisan test --filter=KonfirmasiPengingatTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/KonfirmasiPengingatController.php resources/views/pengingat/konfirmasi.blade.php routes/web.php tests/Feature/Pengingat/KonfirmasiPengingatTest.php
git commit -m "feat(pengingat): halaman & alur konfirmasi minum obat (tutup loop eskalasi)"
```

---

## Task 17: Verifikasi menyeluruh & dokumentasi operasional

**Files:**
- Create: `docs/pengingat-operasional.md`
- Test: seluruh suite

- [ ] **Step 1: Jalankan seluruh test**

Run: `composer test`
Expected: seluruh test PASS (termasuk test existing tidak regресi).

- [ ] **Step 2: Lint**

Run: `vendor/bin/pint`
Expected: tidak ada error format (file diformat).

- [ ] **Step 3: Tulis `docs/pengingat-operasional.md`**

Isi dokumen dengan instruksi deploy:
```markdown
# Operasional Pengingat

## Wajib jalan di produksi (VPS)
1. Cron Laravel scheduler (sekali saja):
   * * * * * cd /path/app && php artisan schedule:run >> /dev/null 2>&1
2. Queue worker (via supervisor):
   php artisan queue:work --queue=default --tries=3 --backoff=30
3. HTTPS wajib (Service Worker + Web Push).

## Konfigurasi
- Kunci VAPID: php artisan tinker → Minishlink\WebPush\VAPID::createVapidKeys()
  → isi VAPID_PUBLIC_KEY & VAPID_PRIVATE_KEY di .env, lalu config:clear.
- WhatsApp: set WA_DRIVER=cloud_api, WA_CLOUD_TOKEN, WA_CLOUD_PHONE_ID di .env.
  Submit template "pengingat_obat" (bahasa id, 4 parameter: nama, obat, jam, link) di Meta Business Manager.
- Atur waktu eskalasi via .env: PENGINGAT_INTERVAL_ULANG, PENGINGAT_WA_PASIEN_MENIT,
  PENGINGAT_WA_PMO_MENIT, PENGINGAT_BATAS_AKHIR_MENIT.

## Demo lokal (Windows)
- composer dev (server+queue+vite) ATAU jalankan terpisah:
  php artisan queue:work
  Task Scheduler Windows memanggil `php artisan schedule:run` tiap menit
  (atau loop manual `php artisan pengingat:tick`).
```

- [ ] **Step 4: Commit**

```bash
git add docs/pengingat-operasional.md
git commit -m "docs(pengingat): panduan operasional deploy (cron, worker, VAPID, WA)"
```

---

## Catatan tindak lanjut (di luar cakupan tahap ini)
- **Pengingat CGD**: butuh tautan pasien pada `jadwal_cgds` (mis. `id_pasien_pmo`). Setelah itu: tambah `materialisasiCgd()` di `PengingatTickService`, set `config('pengingat.aktif.cgd') = true`, template WA `pengingat_cgd`, dan konfirmasi via `PengingatCgdLog`.
- **iOS PWA**: bila nanti ingin push untuk iOS, tambahkan manifest PWA + banner "Add to Home Screen". Saat ini pasien tanpa push tercover WA sejak menit-0.
- **Pembersihan**: job terjadwal harian untuk menghapus `pengingat_kejadian`/`pengingat_kirim_log` lama (mis. > 90 hari).
```
