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
            'waktu_jadwal' => Carbon::parse(now()->toDateString().' 08:00:00'),
            'status' => PengingatKejadian::STATUS_MENUNGGU,
        ]);
    }

    public function test_kanal_whatsapp_memanggil_sender_dan_mencatat_log(): void
    {
        $k = $this->buatKejadian();
        $k->pasien->update(['whatsapp_number' => '08123456789']);

        $fake = new class implements WhatsAppSender
        {
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
        $this->assertSame('628123456789', $fake->dipanggil[0]['noHp']);
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
