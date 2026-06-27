<?php

namespace Tests\Feature\MasterDirektori;

use App\Models\PasienPmo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterPasienTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        // superadmin: lolos role:admin,superadmin + bypass permission middleware
        return User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
    }

    private function pasien(string $nama): User
    {
        $p = User::factory()->create(['role' => 'pasien', 'is_active' => true]);
        PasienPmo::create([
            'id_user' => $p->id, 'pmo_user_id' => null, 'nama_pasien' => $nama,
            'nik' => fake()->numerify('################'), 'nama_pmo' => '-',
            'jenis_pmo' => 'Keluarga', 'tanggal_regis' => now()->toDateString(),
            'status_diabetes' => 'Tipe 2', 'is_active' => true,
        ]);

        return $p;
    }

    public function test_admin_melihat_direktori_pasien(): void
    {
        $this->pasien('Siti Aminah');

        $this->actingAs($this->admin())->get('/admin/master/pasien')
            ->assertOk()->assertSee('Siti Aminah');
    }

    public function test_admin_melihat_detail_pasien(): void
    {
        $p = $this->pasien('Siti Aminah');

        $this->actingAs($this->admin())->get("/admin/master/pasien/{$p->id}")
            ->assertOk()->assertSee('Siti Aminah');
    }

    public function test_detail_non_pasien_404(): void
    {
        $bukanPasien = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($this->admin())->get("/admin/master/pasien/{$bukanPasien->id}")
            ->assertNotFound();
    }

    public function test_non_admin_ditolak(): void
    {
        $pasien = User::factory()->create(['role' => 'pasien', 'is_active' => true]);

        $this->actingAs($pasien)->get('/admin/master/pasien')->assertForbidden();
    }
}
