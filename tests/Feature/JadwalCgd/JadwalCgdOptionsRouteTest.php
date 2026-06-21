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
        // Gunakan superadmin agar lolos semua permission check tanpa perlu seed RolePermissionSeeder
        $admin = User::factory()->create(['role' => 'superadmin']);

        PasienPmo::factory()->create(['nama_pasien' => 'Budi']);

        $res = $this->actingAs($admin)->getJson(route('jadwal-cgd.options.pasien-pmo'));

        $res->assertOk()->assertJsonStructure(['data' => [['id', 'nama_pasien', 'label']]]);
    }
}
