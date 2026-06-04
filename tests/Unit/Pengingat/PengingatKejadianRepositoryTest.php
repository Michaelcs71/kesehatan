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
