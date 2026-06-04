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
        Carbon::setTestNow(Carbon::parse(now()->toDateString().' 08:05:00'));
        JadwalMinumObat::factory()->create(['jam_mulai' => '08:00:00', 'frekuensi_per_hari' => 1, 'tgl_mulai' => now()->subDay()->toDateString()]);

        PengingatTickService::materialisasiMo();

        $this->assertSame(1, PengingatKejadian::count());
        $this->assertSame('menunggu', PengingatKejadian::first()->status);
    }

    public function test_materialisasi_idempoten_walau_dipanggil_dua_kali(): void
    {
        Carbon::setTestNow(Carbon::parse(now()->toDateString().' 08:05:00'));
        JadwalMinumObat::factory()->create(['jam_mulai' => '08:00:00', 'frekuensi_per_hari' => 1, 'tgl_mulai' => now()->subDay()->toDateString()]);

        PengingatTickService::materialisasiMo();
        PengingatTickService::materialisasiMo();

        $this->assertSame(1, PengingatKejadian::count());
    }

    public function test_materialisasi_abaikan_slot_belum_tiba(): void
    {
        Carbon::setTestNow(Carbon::parse(now()->toDateString().' 07:00:00'));
        JadwalMinumObat::factory()->create(['jam_mulai' => '08:00:00', 'frekuensi_per_hari' => 1, 'tgl_mulai' => now()->subDay()->toDateString()]);

        PengingatTickService::materialisasiMo();

        $this->assertSame(0, PengingatKejadian::count());
    }

    public function test_proses_mendispatch_job_dan_set_terlewat(): void
    {
        Queue::fake();
        $jadwal = JadwalMinumObat::factory()->create(['jam_mulai' => '08:00:00', 'frekuensi_per_hari' => 1]);
        $pp = $jadwal->pasienPmo;

        $baru = PengingatKejadian::create([
            'jenis' => 'mo', 'jadwal_id' => $jadwal->id, 'id_pasien_pmo' => $pp->id,
            'user_pasien_id' => $pp->id_user, 'user_pmo_id' => $pp->pmo_user_id,
            'waktu_jadwal' => now()->copy()->subMinutes(1), 'status' => PengingatKejadian::STATUS_MENUNGGU,
        ]);
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
