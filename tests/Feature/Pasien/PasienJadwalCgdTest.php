<?php

namespace Tests\Feature\Pasien;

use App\Models\JadwalCgd;
use App\Models\JadwalCgdPeserta;
use App\Models\PasienPmo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasienJadwalCgdTest extends TestCase
{
    use RefreshDatabase;

    public function test_pasien_melihat_jadwal_cgd_miliknya(): void
    {
        $p = User::factory()->create(['role' => 'pasien', 'is_active' => true]);
        $pp = PasienPmo::create([
            'id_user' => $p->id, 'pmo_user_id' => null, 'nama_pasien' => $p->name,
            'nik' => fake()->numerify('################'), 'nama_pmo' => '-',
            'jenis_pmo' => 'Keluarga', 'tanggal_regis' => now()->toDateString(),
            'status_diabetes' => 'Tipe 2', 'is_active' => true,
        ]);
        $j = JadwalCgd::factory()->create(['tempat' => 'Puskesmas Melati']);
        JadwalCgdPeserta::create([
            'jadwal_cgd_id' => $j->id, 'id_pasien_pmo' => $pp->id,
            'nama_pasien' => $p->name, 'nama_pmo' => '-',
        ]);

        $this->actingAs($p)->get('/pasien/jadwal-cgd')
            ->assertOk()
            ->assertSee('Puskesmas Melati');
    }

    public function test_non_pasien_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->get('/pasien/jadwal-cgd')->assertForbidden();
    }
}
