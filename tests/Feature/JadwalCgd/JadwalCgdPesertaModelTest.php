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
