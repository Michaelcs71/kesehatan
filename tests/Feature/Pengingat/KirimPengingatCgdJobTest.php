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
