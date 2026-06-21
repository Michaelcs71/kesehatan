# Pengingat CGD Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mengirim pengingat Cek Gula Darah (CGD) ke pasien & PMO untuk tiap jadwal CGD: sekali saat jadwal dibuat/diaktifkan dan sekali H-1.

**Architecture:** Tabel pivot `jadwal_cgd_peserta` menautkan jadwal CGD ke banyak pasien sekaligus merangkap *ledger pengiriman* (kolom `dikirim_dibuat_pada` / `dikirim_h1_pada` → idempoten). Cron `pengingat:tick` yang sudah ada memanggil `PengingatTickService::prosesCgd()` yang memindai peserta jatuh tempo dan men-dispatch `KirimPengingatCgdJob`. Job mengirim Web Push (bila ada subscription) atau WhatsApp ke pasien dan PMO. Mesin MO (`pengingat_kejadian`, `KirimPengingatJob`) tidak diubah.

**Tech Stack:** Laravel 12, PHP 8.2, MySQL (test: sqlite :memory:), Eloquent, Laravel Queue, Web Push (`minishlink/web-push`), WhatsApp Cloud API, Blade + jQuery.

## Global Constraints

- Domain language Indonesian: nama entitas, method, pesan, komentar dalam Bahasa Indonesia.
- Service & Repository memakai **static methods** secara eksklusif (pola codebase).
- Primary key UUID (`HasUuids`); penulisan DB dibungkus `DB::transaction(...)` di Repository.
- Controller tipis: hanya validasi (FormRequest `->validated()`) lalu delegasi ke Service.
- Mesin MO tidak boleh berubah perilaku: `KirimPengingatJob`, `PengingatTickService::materialisasiMo()` / `proses()` tetap.
- Test dijalankan dengan `php artisan test` (sqlite :memory:); migration harus kompatibel sqlite.
- Format/lint: `vendor/bin/pint` sebelum commit.

---

### Task 1: Tabel & model peserta `jadwal_cgd_peserta`

**Files:**
- Create: `database/migrations/2026_06_21_100000_create_jadwal_cgd_peserta_table.php`
- Create: `app/Models/JadwalCgdPeserta.php`
- Modify: `app/Models/JadwalCgd.php` (tambah relasi `peserta`)
- Create: `database/factories/JadwalCgdFactory.php`
- Create: `database/factories/JadwalCgdPesertaFactory.php`
- Test: `tests/Feature/JadwalCgd/JadwalCgdPesertaModelTest.php`

**Interfaces:**
- Produces:
  - Tabel `jadwal_cgd_peserta(id, jadwal_cgd_id, id_pasien_pmo, nama_pasien, nama_pmo, dikirim_dibuat_pada?, dikirim_h1_pada?, timestamps)`, unique `(jadwal_cgd_id, id_pasien_pmo)`.
  - `App\Models\JadwalCgdPeserta` dengan `belongsTo jadwalCgd`, `belongsTo pasienPmo`; casts `dikirim_dibuat_pada`/`dikirim_h1_pada` → `datetime`.
  - `JadwalCgd::peserta(): HasMany` (FK `jadwal_cgd_id`).
  - `JadwalCgd::factory()`, `JadwalCgdPeserta::factory()`.

- [ ] **Step 1: Tulis test yang gagal**

Create `tests/Feature/JadwalCgd/JadwalCgdPesertaModelTest.php`:

```php
<?php

namespace Tests\Feature\JadwalCgd;

use App\Models\JadwalCgd;
use App\Models\JadwalCgdPeserta;
use App\Models\PasienPmo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalCgdPesertaModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_jadwal_punya_banyak_peserta(): void
    {
        $jadwal = JadwalCgd::factory()->create();
        $pp = PasienPmo::factory()->create();

        $peserta = JadwalCgdPeserta::create([
            'jadwal_cgd_id' => $jadwal->id,
            'id_pasien_pmo' => $pp->id,
            'nama_pasien' => $pp->nama_pasien,
            'nama_pmo' => $pp->nama_pmo,
        ]);

        $this->assertCount(1, $jadwal->refresh()->peserta);
        $this->assertSame($jadwal->id, $peserta->jadwalCgd->id);
        $this->assertSame($pp->id, $peserta->pasienPmo->id);
        $this->assertNull($peserta->dikirim_dibuat_pada);
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --filter=JadwalCgdPesertaModelTest`
Expected: FAIL ("Class ... JadwalCgdPeserta not found" / tabel tidak ada).

- [ ] **Step 3: Buat migration**

Create `database/migrations/2026_06_21_100000_create_jadwal_cgd_peserta_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_cgd_peserta', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('jadwal_cgd_id')->constrained('jadwal_cgds')->cascadeOnDelete();
            $table->foreignUuid('id_pasien_pmo')->constrained('pasien_pmos');

            $table->string('nama_pasien');                 // snapshot
            $table->string('nama_pmo')->nullable();        // snapshot

            $table->dateTime('dikirim_dibuat_pada')->nullable();
            $table->dateTime('dikirim_h1_pada')->nullable();

            $table->timestamps();

            $table->unique(['jadwal_cgd_id', 'id_pasien_pmo'], 'uq_cgd_peserta');
            $table->index('dikirim_dibuat_pada');
            $table->index('dikirim_h1_pada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_cgd_peserta');
    }
};
```

- [ ] **Step 4: Buat model `JadwalCgdPeserta`**

Create `app/Models/JadwalCgdPeserta.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalCgdPeserta extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'jadwal_cgd_peserta';

    protected $fillable = [
        'jadwal_cgd_id',
        'id_pasien_pmo',
        'nama_pasien',
        'nama_pmo',
        'dikirim_dibuat_pada',
        'dikirim_h1_pada',
    ];

    protected function casts(): array
    {
        return [
            'dikirim_dibuat_pada' => 'datetime',
            'dikirim_h1_pada' => 'datetime',
        ];
    }

    public function jadwalCgd(): BelongsTo
    {
        return $this->belongsTo(JadwalCgd::class, 'jadwal_cgd_id');
    }

    public function pasienPmo(): BelongsTo
    {
        return $this->belongsTo(PasienPmo::class, 'id_pasien_pmo');
    }
}
```

- [ ] **Step 5: Tambah relasi `peserta` di `JadwalCgd`**

Modify `app/Models/JadwalCgd.php`. Tambah import di blok use (setelah `use Illuminate\Database\Eloquent\Relations\BelongsTo;`):

```php
use Illuminate\Database\Eloquent\Relations\HasMany;
```

Di bagian `// ============ RELATIONS ============`, tambah:

```php
    public function peserta(): HasMany
    {
        return $this->hasMany(JadwalCgdPeserta::class, 'jadwal_cgd_id');
    }
```

- [ ] **Step 6: Buat factory**

Create `database/factories/JadwalCgdFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\JadwalCgd;
use Illuminate\Database\Eloquent\Factories\Factory;

class JadwalCgdFactory extends Factory
{
    protected $model = JadwalCgd::class;

    public function definition(): array
    {
        return [
            'tgl_input' => now()->toDateString(),
            'tgl_jadwal_cgd' => now()->addDays(3)->toDateString(),
            'jam_mulai' => '07:00:00',
            'jam_berakhir' => '10:00:00',
            'puasa' => 'Wajib',
            'tempat' => $this->faker->city(),
            'status' => 'aktif',
        ];
    }
}
```

Create `database/factories/JadwalCgdPesertaFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\JadwalCgd;
use App\Models\JadwalCgdPeserta;
use App\Models\PasienPmo;
use Illuminate\Database\Eloquent\Factories\Factory;

class JadwalCgdPesertaFactory extends Factory
{
    protected $model = JadwalCgdPeserta::class;

    public function definition(): array
    {
        return [
            'jadwal_cgd_id' => JadwalCgd::factory(),
            'id_pasien_pmo' => PasienPmo::factory(),
            'nama_pasien' => $this->faker->name(),
            'nama_pmo' => $this->faker->name(),
            'dikirim_dibuat_pada' => null,
            'dikirim_h1_pada' => null,
        ];
    }
}
```

- [ ] **Step 7: Jalankan test, pastikan lulus**

Run: `php artisan test --filter=JadwalCgdPesertaModelTest`
Expected: PASS.

- [ ] **Step 8: Format & commit**

```bash
vendor/bin/pint app/Models/JadwalCgdPeserta.php app/Models/JadwalCgd.php database/factories/JadwalCgdFactory.php database/factories/JadwalCgdPesertaFactory.php
git add database/migrations/2026_06_21_100000_create_jadwal_cgd_peserta_table.php app/Models/JadwalCgdPeserta.php app/Models/JadwalCgd.php database/factories/JadwalCgdFactory.php database/factories/JadwalCgdPesertaFactory.php tests/Feature/JadwalCgd/JadwalCgdPesertaModelTest.php
git commit -m "feat(cgd): tabel & model peserta jadwal CGD"
```

---

### Task 2: Perluas `pengingat_kirim_log` untuk CGD

**Files:**
- Create: `database/migrations/2026_06_21_100001_add_cgd_columns_to_pengingat_kirim_log.php`
- Modify: `app/Models/PengingatKirimLog.php`
- Test: `tests/Feature/Pengingat/PengingatKirimLogCgdTest.php`

**Interfaces:**
- Produces: `pengingat_kirim_log.kejadian_id` jadi nullable; kolom baru nullable `peserta_id`, `fase`. `PengingatKirimLog` fillable bertambah `peserta_id`, `fase`.

Catatan: jalur MO tetap mengisi `kejadian_id`; jalur CGD mengisi `peserta_id` + `fase` dan membiarkan `kejadian_id` null.

- [ ] **Step 1: Tulis test yang gagal**

Create `tests/Feature/Pengingat/PengingatKirimLogCgdTest.php`:

```php
<?php

namespace Tests\Feature\Pengingat;

use App\Models\PengingatKirimLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengingatKirimLogCgdTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_cgd_boleh_tanpa_kejadian_id(): void
    {
        $log = PengingatKirimLog::create([
            'kejadian_id' => null,
            'peserta_id' => 'a1b2c3d4-0000-0000-0000-000000000000',
            'fase' => 'dibuat',
            'kanal' => 'whatsapp',
            'target' => 'pasien',
            'status' => 'terkirim',
        ]);

        $this->assertNull($log->kejadian_id);
        $this->assertSame('dibuat', $log->fresh()->fase);
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --filter=PengingatKirimLogCgdTest`
Expected: FAIL (kolom `kejadian_id` NOT NULL / `peserta_id` tidak ada).

- [ ] **Step 3: Buat migration**

Create `database/migrations/2026_06_21_100001_add_cgd_columns_to_pengingat_kirim_log.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengingat_kirim_log', function (Blueprint $table) {
            $table->uuid('kejadian_id')->nullable()->change();
            $table->uuid('peserta_id')->nullable()->after('kejadian_id');
            $table->string('fase', 10)->nullable()->after('target'); // 'dibuat' | 'h1'
            $table->index('peserta_id');
        });
    }

    public function down(): void
    {
        Schema::table('pengingat_kirim_log', function (Blueprint $table) {
            $table->dropIndex(['peserta_id']);
            $table->dropColumn(['peserta_id', 'fase']);
            $table->uuid('kejadian_id')->nullable(false)->change();
        });
    }
};
```

- [ ] **Step 4: Update fillable model**

Modify `app/Models/PengingatKirimLog.php` — ganti array `$fillable`:

```php
    protected $fillable = [
        'kejadian_id',
        'peserta_id',
        'kanal',
        'target',
        'fase',
        'status',
        'error',
    ];
```

- [ ] **Step 5: Jalankan test, pastikan lulus**

Run: `php artisan test --filter=PengingatKirimLogCgdTest`
Expected: PASS.

- [ ] **Step 6: Format & commit**

```bash
vendor/bin/pint app/Models/PengingatKirimLog.php
git add database/migrations/2026_06_21_100001_add_cgd_columns_to_pengingat_kirim_log.php app/Models/PengingatKirimLog.php tests/Feature/Pengingat/PengingatKirimLogCgdTest.php
git commit -m "feat(cgd): kolom CGD pada pengingat_kirim_log"
```

---

### Task 3: Config pengingat CGD

**Files:**
- Modify: `config/pengingat.php`

**Interfaces:**
- Produces: `config('pengingat.aktif.cgd')` = true; `config('pengingat.cgd.jam_h1')`; `config('pengingat.whatsapp.cloud_api.template_cgd')`.

- [ ] **Step 1: Edit config**

Modify `config/pengingat.php`.

Ganti blok `'aktif' => [...]`:

```php
    'aktif' => [
        'mo' => true,
        'cgd' => true,
    ],

    'cgd' => [
        // Jam pengiriman pengingat H-1 (sehari sebelum tgl_jadwal_cgd).
        'jam_h1' => env('PENGINGAT_CGD_JAM_H1', '17:00'),
    ],
```

Pada blok `'whatsapp' => ['cloud_api' => [...]]`, tambah baris setelah `'template_mo' => ...,`:

```php
            'template_cgd' => env('WA_TEMPLATE_CGD', 'pengingat_cgd'),
```

- [ ] **Step 2: Verifikasi config termuat**

Run: `php artisan config:clear && php artisan tinker --execute="echo config('pengingat.aktif.cgd') ? 'on' : 'off'; echo config('pengingat.cgd.jam_h1');"`
Expected: output `on17:00`.

- [ ] **Step 3: Commit**

```bash
git add config/pengingat.php
git commit -m "feat(cgd): aktifkan config pengingat CGD + jam H-1 + template WA"
```

---

### Task 4: Job `KirimPengingatCgdJob`

**Files:**
- Create: `app/Jobs/KirimPengingatCgdJob.php`
- Test: `tests/Feature/Pengingat/KirimPengingatCgdJobTest.php`

**Interfaces:**
- Consumes: `JadwalCgdPeserta` (with `jadwalCgd`, `pasienPmo`), `PushSubscription`, `WebPushSender::kirimKeUser`, `WhatsAppSender::kirimTemplate`, `PengingatKirimLog`, config CGD.
- Produces: `KirimPengingatCgdJob::dispatch(string $pesertaId, string $fase)` (`$fase` ∈ `'dibuat'|'h1'`). Mengirim ke pasien (push bila ada subscription, selain itu WA) dan ke PMO (bila ada `pmo_user_id`). Mencatat tiap upaya ke `pengingat_kirim_log`.
- WA template param order: `[$namaTujuan, $tanggal, $jam, $tempat, $statusPuasa]`.

- [ ] **Step 1: Tulis test yang gagal**

Create `tests/Feature/Pengingat/KirimPengingatCgdJobTest.php`:

```php
<?php

namespace Tests\Feature\Pengingat;

use App\Jobs\KirimPengingatCgdJob;
use App\Models\JadwalCgd;
use App\Models\JadwalCgdPeserta;
use App\Models\PasienPmo;
use App\Models\PengingatKirimLog;
use App\Models\User;
use App\Services\Whatsapp\WhatsAppSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class KirimPengingatCgdJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_tanpa_push_kirim_wa_ke_pasien_dan_pmo(): void
    {
        $pasien = User::factory()->create(['whatsapp_number' => '081234567890']);
        $pmo = User::factory()->create(['whatsapp_number' => '081200000000']);
        $pp = PasienPmo::factory()->create(['id_user' => $pasien->id, 'pmo_user_id' => $pmo->id]);
        $jadwal = JadwalCgd::factory()->create(['status' => 'aktif']);
        $peserta = JadwalCgdPeserta::factory()->create([
            'jadwal_cgd_id' => $jadwal->id,
            'id_pasien_pmo' => $pp->id,
            'nama_pasien' => $pp->nama_pasien,
            'nama_pmo' => $pp->nama_pmo,
        ]);

        $wa = Mockery::mock(WhatsAppSender::class);
        $wa->shouldReceive('kirimTemplate')->twice()->andReturnTrue();
        $this->app->instance(WhatsAppSender::class, $wa);

        (new KirimPengingatCgdJob($peserta->id, 'dibuat'))->handle($wa, app(\App\Services\WebPush\WebPushSender::class));

        $this->assertSame(2, PengingatKirimLog::where('peserta_id', $peserta->id)->count());
        $this->assertSame(1, PengingatKirimLog::where('peserta_id', $peserta->id)->where('target', 'pmo')->count());
    }

    public function test_jadwal_nonaktif_tidak_kirim(): void
    {
        $pp = PasienPmo::factory()->create();
        $jadwal = JadwalCgd::factory()->create(['status' => 'nonaktif']);
        $peserta = JadwalCgdPeserta::factory()->create([
            'jadwal_cgd_id' => $jadwal->id,
            'id_pasien_pmo' => $pp->id,
        ]);

        $wa = Mockery::mock(WhatsAppSender::class);
        $wa->shouldNotReceive('kirimTemplate');
        $this->app->instance(WhatsAppSender::class, $wa);

        (new KirimPengingatCgdJob($peserta->id, 'dibuat'))->handle($wa, app(\App\Services\WebPush\WebPushSender::class));

        $this->assertSame(0, PengingatKirimLog::where('peserta_id', $peserta->id)->count());
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --filter=KirimPengingatCgdJobTest`
Expected: FAIL ("Class ... KirimPengingatCgdJob not found").

- [ ] **Step 3: Implementasi job**

Create `app/Jobs/KirimPengingatCgdJob.php`:

```php
<?php

namespace App\Jobs;

use App\Models\JadwalCgd;
use App\Models\JadwalCgdPeserta;
use App\Models\PengingatKirimLog;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPush\WebPushSender;
use App\Services\Whatsapp\WhatsAppSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class KirimPengingatCgdJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120];

    public function __construct(
        public string $pesertaId,
        public string $fase,   // 'dibuat' | 'h1'
    ) {}

    public function handle(WhatsAppSender $wa, WebPushSender $push): void
    {
        $peserta = JadwalCgdPeserta::with(['jadwalCgd', 'pasienPmo'])->find($this->pesertaId);
        if (! $peserta || ! $peserta->jadwalCgd || $peserta->jadwalCgd->status !== 'aktif') {
            return;
        }

        $jadwal = $peserta->jadwalCgd;
        $pp = $peserta->pasienPmo;

        if ($pp?->id_user) {
            $this->kirimKe($pp->id_user, 'pasien', $peserta->nama_pasien, $peserta, $jadwal, $wa, $push);
        }
        if ($pp?->pmo_user_id) {
            $this->kirimKe($pp->pmo_user_id, 'pmo', $peserta->nama_pmo ?? 'PMO', $peserta, $jadwal, $wa, $push);
        }
    }

    private function kirimKe(
        string $userId,
        string $target,
        string $namaTujuan,
        JadwalCgdPeserta $peserta,
        JadwalCgd $jadwal,
        WhatsAppSender $wa,
        WebPushSender $push,
    ): void {
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        $tanggal = $jadwal->tgl_jadwal_cgd->format('d M Y');
        $jam = substr((string) $jadwal->jam_mulai, 0, 5);
        $statusPuasa = $jadwal->puasa === 'Wajib' ? 'Wajib puasa' : 'Tidak perlu puasa';
        $prefix = $this->fase === 'h1' ? 'Besok' : 'Info jadwal';
        $url = url("/jadwal-cgd/{$jadwal->id}");

        $punyaPush = PushSubscription::where('user_id', $userId)->exists();

        try {
            if ($punyaPush) {
                $judul = $target === 'pmo' ? 'Pengingat CGD pasien Anda' : 'Pengingat Cek Gula Darah';
                $isi = "{$prefix}: cek gula darah {$tanggal} jam {$jam} di {$jadwal->tempat}. {$statusPuasa}.";
                $push->kirimKeUser($userId, ['judul' => $judul, 'isi' => $isi, 'url' => $url]);
                $this->catat($peserta->id, 'push', $target, 'terkirim', null);
            } else {
                $no = $this->normalkanNomor($user->whatsapp_number);
                if (! $no) {
                    $this->catat($peserta->id, 'whatsapp', $target, 'gagal', 'nomor WA kosong');

                    return;
                }
                $template = config('pengingat.whatsapp.cloud_api.template_cgd', 'pengingat_cgd');
                $wa->kirimTemplate($no, $template, [$namaTujuan, $tanggal, $jam, $jadwal->tempat, $statusPuasa]);
                $this->catat($peserta->id, 'whatsapp', $target, 'terkirim', null);
            }
        } catch (\Throwable $e) {
            $this->catat($peserta->id, $punyaPush ? 'push' : 'whatsapp', $target, 'gagal', $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[pengingat] KirimPengingatCgdJob gagal total', [
            'peserta' => $this->pesertaId, 'fase' => $this->fase, 'error' => $e->getMessage(),
        ]);
    }

    private function catat(string $pesertaId, string $kanal, string $target, string $status, ?string $error): void
    {
        PengingatKirimLog::create([
            'kejadian_id' => null,
            'peserta_id' => $pesertaId,
            'kanal' => $kanal,
            'target' => $target,
            'fase' => $this->fase,
            'status' => $status,
            'error' => $error,
        ]);
    }

    private function normalkanNomor(?string $no): ?string
    {
        if (blank($no)) {
            return null;
        }
        $no = preg_replace('/\D+/', '', $no);
        if (str_starts_with($no, '0')) {
            $no = '62'.substr($no, 1);
        } elseif (! str_starts_with($no, '62')) {
            $no = '62'.$no;
        }

        return $no;
    }
}
```

Catatan: `normalkanNomor` sengaja diduplikasi dari `KirimPengingatJob` agar job CGD terisolasi penuh dari mesin MO (perubahan satu tak memengaruhi yang lain). Metodenya kecil dan tanpa state.

- [ ] **Step 4: Jalankan test, pastikan lulus**

Run: `php artisan test --filter=KirimPengingatCgdJobTest`
Expected: PASS (2 test).

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint app/Jobs/KirimPengingatCgdJob.php
git add app/Jobs/KirimPengingatCgdJob.php tests/Feature/Pengingat/KirimPengingatCgdJobTest.php
git commit -m "feat(cgd): job kirim pengingat CGD (push/WA ke pasien & PMO)"
```

---

### Task 5: `PengingatTickService::prosesCgd()` + hook tick

**Files:**
- Modify: `app/Services/PengingatTickService.php` (tambah `prosesCgd()`, panggil di `jalankan()`)
- Modify: `app/Console/Commands/PengingatTick.php` (deskripsi)
- Test: `tests/Feature/Pengingat/PengingatCgdTickTest.php`

**Interfaces:**
- Consumes: `JadwalCgd` (scope `status=aktif`, `tgl_jadwal_cgd >= hari ini`), relasi `peserta`, `KirimPengingatCgdJob::dispatch`, config `pengingat.cgd.jam_h1`, `pengingat.aktif.cgd`.
- Produces: `PengingatTickService::prosesCgd(): void`. Untuk tiap peserta jadwal aktif & belum lewat: fase `'dibuat'` jika `dikirim_dibuat_pada` null; fase `'h1'` jika `dikirim_h1_pada` null dan `now >= (tgl_jadwal_cgd − 1 hari) jam_h1`. Tiap dispatch menandai kolom waktu terkait (idempoten antar tick). `jalankan()` memanggil `prosesCgd()` saat `config('pengingat.aktif.cgd')`.

- [ ] **Step 1: Tulis test yang gagal**

Create `tests/Feature/Pengingat/PengingatCgdTickTest.php`:

```php
<?php

namespace Tests\Feature\Pengingat;

use App\Jobs\KirimPengingatCgdJob;
use App\Models\JadwalCgd;
use App\Models\JadwalCgdPeserta;
use App\Models\PasienPmo;
use App\Services\PengingatTickService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PengingatCgdTickTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function buatPeserta(string $tglJadwal, string $status = 'aktif'): JadwalCgdPeserta
    {
        $pp = PasienPmo::factory()->create();
        $jadwal = JadwalCgd::factory()->create(['tgl_jadwal_cgd' => $tglJadwal, 'status' => $status]);

        return JadwalCgdPeserta::factory()->create([
            'jadwal_cgd_id' => $jadwal->id,
            'id_pasien_pmo' => $pp->id,
        ]);
    }

    public function test_fase_dibuat_dispatch_sekali_dan_idempoten(): void
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::parse('2026-06-21 09:00:00'));
        $peserta = $this->buatPeserta('2026-06-30'); // jauh di depan → H-1 belum tiba

        PengingatTickService::prosesCgd();
        PengingatTickService::prosesCgd();

        Queue::assertPushed(KirimPengingatCgdJob::class, 1);
        Queue::assertPushed(fn (KirimPengingatCgdJob $j) => $j->fase === 'dibuat');
        $this->assertNotNull($peserta->refresh()->dikirim_dibuat_pada);
        $this->assertNull($peserta->refresh()->dikirim_h1_pada);
    }

    public function test_fase_h1_dikirim_saat_sudah_h1_jam_config(): void
    {
        Queue::fake();
        // Event 2026-06-22 → H-1 = 2026-06-21 17:00. Set now setelahnya.
        Carbon::setTestNow(Carbon::parse('2026-06-21 17:30:00'));
        $peserta = $this->buatPeserta('2026-06-22');
        // Anggap notif "dibuat" sudah pernah terkirim.
        $peserta->forceFill(['dikirim_dibuat_pada' => now()->subMinutes(5)])->save();

        PengingatTickService::prosesCgd();

        Queue::assertPushed(fn (KirimPengingatCgdJob $j) => $j->fase === 'h1' && $j->pesertaId === $peserta->id);
        $this->assertNotNull($peserta->refresh()->dikirim_h1_pada);
    }

    public function test_fase_h1_belum_dikirim_sebelum_jam_config(): void
    {
        Queue::fake();
        // Event 2026-06-23 → H-1 = 2026-06-22 17:00. now masih 2026-06-21.
        Carbon::setTestNow(Carbon::parse('2026-06-21 09:00:00'));
        $peserta = $this->buatPeserta('2026-06-23');
        $peserta->forceFill(['dikirim_dibuat_pada' => now()])->save();

        PengingatTickService::prosesCgd();

        Queue::assertNotPushed(fn (KirimPengingatCgdJob $j) => $j->fase === 'h1');
        $this->assertNull($peserta->refresh()->dikirim_h1_pada);
    }

    public function test_jadwal_nonaktif_atau_lewat_diabaikan(): void
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::parse('2026-06-21 09:00:00'));
        $this->buatPeserta('2026-06-30', 'nonaktif');     // nonaktif
        $this->buatPeserta('2026-06-20', 'aktif');        // sudah lewat

        PengingatTickService::prosesCgd();

        Queue::assertNothingPushed();
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --filter=PengingatCgdTickTest`
Expected: FAIL ("Call to undefined method ...::prosesCgd()").

- [ ] **Step 3: Implementasi `prosesCgd()` + hook**

Modify `app/Services/PengingatTickService.php`.

Tambah import di blok use (setelah `use App\Models\JadwalMinumObat;`):

```php
use App\Jobs\KirimPengingatCgdJob;
use App\Models\JadwalCgd;
use App\Models\JadwalCgdPeserta;
```

Ganti method `jalankan()`:

```php
    public static function jalankan(): void
    {
        if (config('pengingat.aktif.mo')) {
            self::materialisasiMo();
        }
        self::proses();

        if (config('pengingat.aktif.cgd')) {
            self::prosesCgd();
        }
    }
```

Tambah method baru (mis. setelah `proses()`):

```php
    /**
     * Kirim pengingat CGD: sekali saat jadwal aktif (fase 'dibuat') &
     * sekali H-1 (fase 'h1'). Idempoten via penanda waktu di peserta.
     */
    public static function prosesCgd(): void
    {
        $now = Carbon::now();
        $jamH1 = (string) config('pengingat.cgd.jam_h1', '17:00');

        JadwalCgd::query()
            ->where('status', 'aktif')
            ->whereDate('tgl_jadwal_cgd', '>=', $now->toDateString())
            ->with('peserta')
            ->chunk(100, function ($jadwals) use ($now, $jamH1) {
                foreach ($jadwals as $jadwal) {
                    $waktuH1 = Carbon::parse($jadwal->tgl_jadwal_cgd->toDateString().' '.$jamH1)->subDay();

                    foreach ($jadwal->peserta as $peserta) {
                        if ($peserta->dikirim_dibuat_pada === null) {
                            self::dispatchCgd($peserta, 'dibuat', 'dikirim_dibuat_pada', $now);
                        }

                        if ($peserta->dikirim_h1_pada === null && $now->greaterThanOrEqualTo($waktuH1)) {
                            self::dispatchCgd($peserta, 'h1', 'dikirim_h1_pada', $now);
                        }
                    }
                }
            });
    }

    private static function dispatchCgd(JadwalCgdPeserta $peserta, string $fase, string $kolom, Carbon $now): void
    {
        KirimPengingatCgdJob::dispatch($peserta->id, $fase);
        $peserta->forceFill([$kolom => $now])->save();
    }
```

- [ ] **Step 4: Update deskripsi command**

Modify `app/Console/Commands/PengingatTick.php` — ganti baris `protected $description`:

```php
    protected $description = 'Materialisasi & proses pengingat MO + CGD yang jatuh tempo (jalan tiap menit)';
```

- [ ] **Step 5: Jalankan test, pastikan lulus**

Run: `php artisan test --filter=PengingatCgdTickTest`
Expected: PASS (4 test).

- [ ] **Step 6: Pastikan test MO tidak regresi**

Run: `php artisan test --filter=PengingatTickServiceTest`
Expected: PASS (semua test MO tetap hijau).

- [ ] **Step 7: Format & commit**

```bash
vendor/bin/pint app/Services/PengingatTickService.php app/Console/Commands/PengingatTick.php
git add app/Services/PengingatTickService.php app/Console/Commands/PengingatTick.php tests/Feature/Pengingat/PengingatCgdTickTest.php
git commit -m "feat(cgd): prosesCgd di mesin tick (fase dibuat & H-1)"
```

---

### Task 6: Sync peserta di Service/Repo + options pasien

**Files:**
- Modify: `app/Repos/JadwalCgdRepository.php` (sync peserta di create/update, `findJadwalById` eager-load peserta, `getPasienPmoOptions`)
- Modify: `app/Services/JadwalCgdService.php` (teruskan peserta, `getPasienPmoOptions`)
- Test: `tests/Feature/JadwalCgd/JadwalCgdPesertaSyncTest.php`

**Interfaces:**
- Consumes: `PasienPmo` (scope active), tabel pivot dari Task 1.
- Produces:
  - `JadwalCgdRepository::createJadwal(array $data, array $pesertaIds = []): JadwalCgd`
  - `JadwalCgdRepository::updateJadwal(string $id, array $data, ?array $pesertaIds = null): bool` (`null` = jangan ubah peserta)
  - `JadwalCgdRepository::syncPeserta(JadwalCgd $jadwal, array $pesertaIds): void`
  - `JadwalCgdRepository::getPasienPmoOptions(): array` (shape: `[['id','nama_pasien','nama_pmo','label'], ...]`)
  - `JadwalCgdService::getPasienPmoOptions(): array`
  - `findJadwalById` mengembalikan jadwal dengan relasi `peserta`.

- [ ] **Step 1: Tulis test yang gagal**

Create `tests/Feature/JadwalCgd/JadwalCgdPesertaSyncTest.php`:

```php
<?php

namespace Tests\Feature\JadwalCgd;

use App\Models\JadwalCgd;
use App\Models\PasienPmo;
use App\Models\User;
use App\Services\JadwalCgdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalCgdPesertaSyncTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function dataJadwal(array $pesertaIds): array
    {
        return [
            'tgl_jadwal_cgd' => now()->addDays(3)->toDateString(),
            'jam_mulai' => '07:00',
            'jam_berakhir' => '10:00',
            'puasa' => 'Wajib',
            'tempat' => 'Posyandu Uji',
            'catatan' => null,
            'peserta' => $pesertaIds,
        ];
    }

    public function test_create_menyimpan_peserta_dengan_snapshot_nama(): void
    {
        $this->actingAs($this->admin());
        $pp = PasienPmo::factory()->create(['nama_pasien' => 'Budi', 'nama_pmo' => 'Siti']);

        $jadwal = JadwalCgdService::createJadwal($this->dataJadwal([$pp->id]));

        $this->assertCount(1, $jadwal->refresh()->peserta);
        $this->assertSame('Budi', $jadwal->peserta->first()->nama_pasien);
        $this->assertSame('Siti', $jadwal->peserta->first()->nama_pmo);
    }

    public function test_update_menambah_dan_menghapus_peserta(): void
    {
        $this->actingAs($this->admin());
        $a = PasienPmo::factory()->create();
        $b = PasienPmo::factory()->create();

        $jadwal = JadwalCgdService::createJadwal($this->dataJadwal([$a->id]));
        $pesertaLamaId = $jadwal->refresh()->peserta->first()->id;

        // Tandai notif "dibuat" peserta lama sudah terkirim → tak boleh ter-reset.
        $jadwal->peserta()->where('id', $pesertaLamaId)->update(['dikirim_dibuat_pada' => now()]);

        JadwalCgdService::updateJadwal($jadwal->id, [
            'tgl_jadwal_cgd' => $jadwal->tgl_jadwal_cgd->toDateString(),
            'jam_mulai' => '07:00',
            'jam_berakhir' => '10:00',
            'puasa' => 'Wajib',
            'tempat' => 'Posyandu Uji',
            'status' => 'aktif',
            'peserta' => [$b->id], // a dihapus, b ditambah
        ]);

        $peserta = $jadwal->refresh()->peserta;
        $this->assertCount(1, $peserta);
        $this->assertSame($b->id, $peserta->first()->id_pasien_pmo);
        $this->assertNull($peserta->first()->dikirim_dibuat_pada); // peserta baru fresh
    }

    public function test_update_tanpa_key_peserta_tidak_mengubah_peserta(): void
    {
        $this->actingAs($this->admin());
        $a = PasienPmo::factory()->create();
        $jadwal = JadwalCgdService::createJadwal($this->dataJadwal([$a->id]));

        JadwalCgdService::updateJadwal($jadwal->id, [
            'tgl_jadwal_cgd' => $jadwal->tgl_jadwal_cgd->toDateString(),
            'jam_mulai' => '07:00',
            'jam_berakhir' => '10:00',
            'puasa' => 'Tidak',
            'tempat' => 'Posyandu Uji',
            'status' => 'aktif',
            // tanpa 'peserta'
        ]);

        $this->assertCount(1, $jadwal->refresh()->peserta);
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --filter=JadwalCgdPesertaSyncTest`
Expected: FAIL (peserta tidak tersimpan / argumen tidak dikenal).

- [ ] **Step 3: Update Repository**

Modify `app/Repos/JadwalCgdRepository.php`.

Tambah import di blok use (setelah `use App\Models\JadwalCgd;`):

```php
use App\Models\PasienPmo;
```

Ganti `findJadwalById` agar memuat peserta:

```php
    public static function findJadwalById(string $id): ?JadwalCgd
    {
        return JadwalCgd::with([
            'creator:id,name',
            'updater:id,name',
            'peserta:id,jadwal_cgd_id,id_pasien_pmo,nama_pasien,nama_pmo,dikirim_dibuat_pada,dikirim_h1_pada',
        ])->find($id);
    }
```

Ganti `createJadwal`:

```php
    public static function createJadwal(array $data, array $pesertaIds = []): JadwalCgd
    {
        return DB::transaction(function () use ($data, $pesertaIds) {
            $jadwal = JadwalCgd::create($data);
            self::syncPeserta($jadwal, $pesertaIds);

            return $jadwal;
        });
    }
```

Ganti `updateJadwal`:

```php
    public static function updateJadwal(string $id, array $data, ?array $pesertaIds = null): bool
    {
        return DB::transaction(function () use ($id, $data, $pesertaIds) {
            $jadwal = JadwalCgd::find($id);
            if (! $jadwal) {
                return false;
            }

            $ok = $jadwal->update($data);

            if ($pesertaIds !== null) {
                self::syncPeserta($jadwal, $pesertaIds);
            }

            return $ok;
        });
    }
```

Tambah method baru `syncPeserta` (mis. setelah `updateJadwal`):

```php
    /**
     * Sinkronkan peserta jadwal CGD: tambah yang baru (snapshot nama,
     * penanda kirim kosong), hapus yang tak dipilih lagi. Peserta yang
     * tetap ada TIDAK disentuh (penanda kirim dipertahankan).
     *
     * @param  array<int,string>  $pesertaIds  daftar id pasien_pmo
     */
    public static function syncPeserta(JadwalCgd $jadwal, array $pesertaIds): void
    {
        $pasienPmos = PasienPmo::whereIn('id', $pesertaIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $existing = $jadwal->peserta()->get()->keyBy('id_pasien_pmo');

        // Hapus peserta yang tidak dipilih lagi.
        $toRemove = $existing->keys()->diff($pasienPmos->keys());
        if ($toRemove->isNotEmpty()) {
            $jadwal->peserta()->whereIn('id_pasien_pmo', $toRemove->all())->delete();
        }

        // Tambah peserta baru.
        foreach ($pasienPmos as $id => $pp) {
            if (! $existing->has($id)) {
                $jadwal->peserta()->create([
                    'id_pasien_pmo' => $pp->id,
                    'nama_pasien' => $pp->nama_pasien,
                    'nama_pmo' => $pp->nama_pmo,
                ]);
            }
        }
    }
```

Tambah method `getPasienPmoOptions` (mis. setelah `getUpcoming`):

```php
    /**
     * Opsi peserta (semua pasien_pmo aktif) untuk multi-select form CGD.
     */
    public static function getPasienPmoOptions(): array
    {
        return PasienPmo::query()
            ->where('is_active', true)
            ->orderBy('nama_pasien')
            ->get(['id', 'nama_pasien', 'nama_pmo'])
            ->map(fn ($pp) => [
                'id' => $pp->id,
                'nama_pasien' => $pp->nama_pasien,
                'nama_pmo' => $pp->nama_pmo,
                'label' => $pp->nama_pasien.' (PMO: '.($pp->nama_pmo ?? '-').')',
            ])
            ->toArray();
    }
```

- [ ] **Step 4: Update Service**

Modify `app/Services/JadwalCgdService.php`.

Ganti `createJadwal`:

```php
    public static function createJadwal(array $data): JadwalCgd
    {
        $peserta = $data['peserta'] ?? [];
        unset($data['peserta']);

        $data['tgl_input'] = now()->format('Y-m-d');
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? 'aktif';

        return JadwalCgdRepository::createJadwal($data, $peserta);
    }
```

Ganti `updateJadwal`:

```php
    public static function updateJadwal(string $id, array $data): bool
    {
        $peserta = array_key_exists('peserta', $data) ? ($data['peserta'] ?? []) : null;
        unset($data['peserta']);

        $data['updated_by'] = Auth::id();

        return JadwalCgdRepository::updateJadwal($id, $data, $peserta);
    }
```

Tambah method (mis. setelah `getUpcoming`):

```php
    public static function getPasienPmoOptions(): array
    {
        return JadwalCgdRepository::getPasienPmoOptions();
    }
```

- [ ] **Step 5: Jalankan test, pastikan lulus**

Run: `php artisan test --filter=JadwalCgdPesertaSyncTest`
Expected: PASS (3 test).

- [ ] **Step 6: Format & commit**

```bash
vendor/bin/pint app/Repos/JadwalCgdRepository.php app/Services/JadwalCgdService.php
git add app/Repos/JadwalCgdRepository.php app/Services/JadwalCgdService.php tests/Feature/JadwalCgd/JadwalCgdPesertaSyncTest.php
git commit -m "feat(cgd): sync peserta jadwal + opsi pasien_pmo"
```

---

### Task 7: Controller options + route + validasi peserta

**Files:**
- Modify: `app/Http/Controllers/JadwalCgdController.php` (endpoint `pasienPmoOptions`)
- Modify: `routes/web.php` (route options di grup jadwal-cgd)
- Modify: `app/Http/Requests/JadwalCgd/StoreRequest.php` (validasi `peserta`)
- Modify: `app/Http/Requests/JadwalCgd/UpdateRequest.php` (validasi `peserta`)
- Test: `tests/Feature/JadwalCgd/JadwalCgdOptionsRouteTest.php`

**Interfaces:**
- Consumes: `JadwalCgdService::getPasienPmoOptions()`.
- Produces: `GET /jadwal-cgd/options/pasien-pmo` → `{ data: [...] }` (named `jadwal-cgd.options.pasien-pmo`); FormRequest menerima `peserta` = array uuid pasien_pmo (`nullable`).

- [ ] **Step 1: Tulis test yang gagal**

Create `tests/Feature/JadwalCgd/JadwalCgdOptionsRouteTest.php`:

```php
<?php

namespace Tests\Feature\JadwalCgd;

use App\Models\PasienPmo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalCgdOptionsRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_options_pasien_pmo_mengembalikan_data(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        PasienPmo::factory()->create(['nama_pasien' => 'Budi']);

        $res = $this->actingAs($admin)->getJson(route('jadwal-cgd.options.pasien-pmo'));

        $res->assertOk()->assertJsonStructure(['data' => [['id', 'nama_pasien', 'label']]]);
    }
}
```

Catatan: bila pola seeding peran berbeda di test lain, ikuti pola yang sudah dipakai test feature jadwal lain (mis. `tests/Feature/Pengingat/KonfirmasiPengingatTest.php`). Inti yang diuji: route ada & mengembalikan struktur `data`.

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --filter=JadwalCgdOptionsRouteTest`
Expected: FAIL (route `jadwal-cgd.options.pasien-pmo` tidak ada).

- [ ] **Step 3: Tambah endpoint controller**

Modify `app/Http/Controllers/JadwalCgdController.php`. Tambah method (mis. setelah `markSelesai`):

```php
    // ===== Options =====

    public function pasienPmoOptions(): JsonResponse
    {
        return response()->json(['data' => JadwalCgdService::getPasienPmoOptions()]);
    }
```

- [ ] **Step 4: Tambah route**

Modify `routes/web.php`. Di dalam grup `Route::middleware('permission:jadwal-cgd.create')->group(...)` (sekitar baris 400-403), tambah baris:

```php
            Route::get('/options/pasien-pmo', [JadwalCgdController::class, 'pasienPmoOptions'])->name('options.pasien-pmo');
```

- [ ] **Step 5: Validasi peserta di StoreRequest**

Modify `app/Http/Requests/JadwalCgd/StoreRequest.php`. Tambah aturan di `rules()` (setelah `catatan`):

```php
            'peserta' => 'nullable|array',
            'peserta.*' => 'string|exists:pasien_pmos,id',
```

Tambah pesan di `messages()`:

```php
            'peserta.array' => 'Daftar peserta tidak valid.',
            'peserta.*.exists' => 'Salah satu pasien peserta tidak ditemukan.',
```

- [ ] **Step 6: Validasi peserta di UpdateRequest**

Modify `app/Http/Requests/JadwalCgd/UpdateRequest.php` — tambah aturan & pesan yang sama persis seperti Step 5 (`peserta` + `peserta.*`). Buka file dulu untuk menyisipkan di posisi yang sesuai array `rules()` dan `messages()`.

- [ ] **Step 7: Jalankan test, pastikan lulus**

Run: `php artisan test --filter=JadwalCgdOptionsRouteTest`
Expected: PASS.

- [ ] **Step 8: Format & commit**

```bash
vendor/bin/pint app/Http/Controllers/JadwalCgdController.php app/Http/Requests/JadwalCgd/StoreRequest.php app/Http/Requests/JadwalCgd/UpdateRequest.php
git add app/Http/Controllers/JadwalCgdController.php routes/web.php app/Http/Requests/JadwalCgd/StoreRequest.php app/Http/Requests/JadwalCgd/UpdateRequest.php tests/Feature/JadwalCgd/JadwalCgdOptionsRouteTest.php
git commit -m "feat(cgd): endpoint opsi peserta + validasi peserta"
```

---

### Task 8: UI form & show — multi-select peserta

**Files:**
- Modify: `resources/views/jadwal-cgd/form.blade.php` (multi-select peserta + JS load/preselect/submit)
- Modify: `resources/views/jadwal-cgd/show.blade.php` (tampilkan peserta + status kirim)

**Interfaces:**
- Consumes: `GET jadwal-cgd.options.pasien-pmo` (`{data:[{id,nama_pasien,nama_pmo,label}]}`); `showData` mengembalikan jadwal dengan `peserta:[{id_pasien_pmo,...}]`.
- Produces: form mengirim `peserta` (array uuid) saat store/update.

Catatan: tidak ada test otomatis untuk Blade — verifikasi manual di Step akhir.

- [ ] **Step 1: Tambah kartu multi-select peserta di form**

Modify `resources/views/jadwal-cgd/form.blade.php`. Di kolom kiri (`<div class="col-lg-8">`), setelah card "Tempat & Detail" (`</x-card>` di sekitar baris 100), tambah:

```blade
                <div class="mt-3"></div>

                {{-- ============ PESERTA ============ --}}
                <x-card title="Peserta & Pengingat" icon="ri-group-line">
                    <label for="peserta" class="form-label">Pasien Peserta</label>
                    <select id="peserta" name="peserta[]" class="form-select" multiple size="8"></select>
                    <div class="invalid-feedback"></div>
                    <small class="text-muted d-block mt-1">
                        Pasien terpilih akan menerima pengingat (pasien &amp; PMO): sekali saat jadwal
                        aktif dan sekali H-1. Tahan Ctrl/Cmd untuk memilih lebih dari satu.
                    </small>
                </x-card>
```

- [ ] **Step 2: Tambah route options ke CONFIG JS**

Di blok `CONFIG.ROUTES` (sekitar baris 156-161), tambah baris:

```blade
                    OPTIONS_PESERTA: '{{ route('jadwal-cgd.options.pasien-pmo') }}',
```

- [ ] **Step 3: Muat opsi peserta saat init**

Di fungsi `init()` (sekitar baris 169), panggil loader sebelum load data edit:

```javascript
            async function init() {
                await loadPesertaOptions();
                if (CONFIG.IS_EDIT) {
                    await loadExistingData();
                }
                $form.on('submit', submitForm);
            }

            async function loadPesertaOptions() {
                try {
                    const res = await $.ajax({ url: CONFIG.ROUTES.OPTIONS_PESERTA, method: 'GET' });
                    const $sel = $('#peserta').empty();
                    (res.data || []).forEach(function(p) {
                        $sel.append(new Option(p.label, p.id));
                    });
                } catch (e) {
                    // opsi gagal dimuat — biarkan select kosong
                }
            }
```

- [ ] **Step 4: Preselect peserta saat edit**

Di akhir `loadExistingData()` (setelah blok radio puasa, sebelum `catch`), tambah:

```javascript
                    // Preselect peserta
                    const ids = (data.peserta || []).map(function(p) { return p.id_pasien_pmo; });
                    $('#peserta').val(ids);
```

- [ ] **Step 5: Sertakan peserta saat submit**

Di `submitForm()`, pada objek `data` (sekitar baris 215-222), tambah field:

```javascript
                    peserta: $('#peserta').val() || [],
```

- [ ] **Step 6: Tampilkan peserta di show**

Modify `resources/views/jadwal-cgd/show.blade.php`. Buka file untuk melihat strukturnya, lalu tambahkan satu kartu daftar peserta yang membaca `data.peserta` (id_pasien_pmo, nama_pasien, nama_pmo, dikirim_dibuat_pada, dikirim_h1_pada). Render baris tabel: Nama Pasien | PMO | Pengingat "Dibuat" (badge: terkirim bila `dikirim_dibuat_pada` ada, selain itu "menunggu") | Pengingat "H-1" (idem `dikirim_h1_pada`). Ikuti gaya komponen `x-card` + tabel Bootstrap yang sudah dipakai di show. (showData dari Task 7 sudah mengembalikan `peserta`.)

- [ ] **Step 7: Build asset & verifikasi manual**

Run: `npm run build`

Verifikasi manual (butuh DB ter-seed + login admin):
1. Buka `/jadwal-cgd/create` → kartu "Peserta & Pengingat" muncul, multi-select terisi daftar pasien.
2. Buat jadwal dengan ≥1 peserta → cek tabel `jadwal_cgd_peserta` terisi (snapshot nama, `dikirim_* = null`).
3. Jalankan `php artisan pengingat:tick` → cek log queue/`pengingat_kirim_log` (WA driver `log` di dev) menunjukkan fase `dibuat`.
4. Buka `/jadwal-cgd/{id}` → daftar peserta + status pengingat tampil.
5. Edit jadwal, ubah peserta → pivot ter-sync.

- [ ] **Step 8: Commit**

```bash
git add resources/views/jadwal-cgd/form.blade.php resources/views/jadwal-cgd/show.blade.php
git commit -m "feat(cgd): UI multi-select peserta + status pengingat di show"
```

---

### Task 9: Verifikasi menyeluruh

**Files:** (tidak ada perubahan kode; gerbang akhir)

- [ ] **Step 1: Jalankan seluruh test**

Run: `php artisan test`
Expected: Semua hijau kecuali 2 test auth Breeze yang memang pre-existing merah (lihat memori `auth-tests-pre-existing-fail`). Tidak ada regresi baru pada modul MO/CGD.

- [ ] **Step 2: Lint final**

Run: `vendor/bin/pint --test`
Expected: tidak ada file yang perlu diformat (atau jalankan `vendor/bin/pint` lalu commit).

- [ ] **Step 3: Cek migrasi bersih**

Run: `php artisan migrate:fresh --seed`
Expected: sukses tanpa error (tabel `jadwal_cgd_peserta` & kolom baru `pengingat_kirim_log` terbentuk).

- [ ] **Step 4: Commit penutup (bila ada perubahan format)**

```bash
git add -A
git commit -m "chore(cgd): rapikan format & verifikasi akhir"
```

---

## Catatan operasional (di luar kode)

- Pengingat CGD ikut cron yang sama dengan MO: `* * * * * php artisan schedule:run` + `queue:work`. Pastikan `pengingat:tick` terjadwal (cek `routes/console.php`).
- WhatsApp produksi perlu template `pengingat_cgd` terdaftar di Meta dengan 5 parameter berurutan: nama, tanggal, jam, tempat, status puasa. Dev memakai `WA_DRIVER=log`.
- Web Push perlu kunci VAPID di `.env` (lihat `docs/pengingat-operasional.md`); tanpa subscription pasien, pengingat otomatis jatuh ke WhatsApp.

## Self-Review

- **Spec coverage:** tabel peserta (T1) ✓; kirim log nullable (T2) ✓; config aktif.cgd/jam_h1/template_cgd (T3) ✓; job fire-once pasien+PMO, push→WA fallback (T4) ✓; prosesCgd fase dibuat+H1 via tick, idempoten (T5) ✓; sync peserta + options (T6) ✓; endpoint+route+validasi (T7) ✓; UI form+show (T8) ✓; verifikasi (T9) ✓. Mesin MO tak diubah ✓ (hanya hook tambahan di `jalankan()`).
- **Placeholder scan:** tidak ada TBD/TODO; semua step berisi kode/perintah konkret. Step yang menyebut "buka file dulu" (T6 sync sudah kode penuh; T7 UpdateRequest & T8 show) disertai instruksi spesifik karena isi file belum dibaca utuh — penyisipan mengikuti pola identik yang sudah ditunjukkan.
- **Type consistency:** `createJadwal(array,$pesertaIds=[])`, `updateJadwal(string,array,?array=null)`, `syncPeserta(JadwalCgd,array)`, `getPasienPmoOptions():array`, `KirimPengingatCgdJob(string $pesertaId,string $fase)`, `prosesCgd():void` — konsisten dipakai antar task (T4↔T5, T6↔T7).
