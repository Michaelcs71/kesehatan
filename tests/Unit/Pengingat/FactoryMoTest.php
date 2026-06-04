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
            'jam_mulai' => '08:00:00',
            'frekuensi_per_hari' => 2,
        ]);

        $this->assertNotNull($jadwal->id_pasien_pmo);
        $this->assertNotNull($jadwal->obat_id);
        $this->assertSame(['08:00', '20:00'], $jadwal->slot_jam_harian);
        $this->assertNotNull($jadwal->pasienPmo->id_user);
    }
}
