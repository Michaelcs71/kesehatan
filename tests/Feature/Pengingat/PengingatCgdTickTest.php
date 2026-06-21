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

    public function test_fase_h1_tidak_dikirim_untuk_jadwal_hari_ini(): void
    {
        Queue::fake();
        // Jadwal hari ini (2026-06-21). H-1 window (2026-06-20 17:00) sudah lewat.
        // Notif "dibuat" sudah terkirim → hanya h1 yang berpotensi dikirm.
        // Harapan: h1 TIDAK dikirim karena event-nya hari ini (bukan masa depan).
        Carbon::setTestNow(Carbon::parse('2026-06-21 18:00:00'));
        $peserta = $this->buatPeserta('2026-06-21');
        $peserta->forceFill(['dikirim_dibuat_pada' => now()->subHours(2)])->save();

        PengingatTickService::prosesCgd();

        Queue::assertNotPushed(fn (KirimPengingatCgdJob $j) => $j->fase === 'h1');
        $this->assertNull($peserta->refresh()->dikirim_h1_pada);
    }
}
