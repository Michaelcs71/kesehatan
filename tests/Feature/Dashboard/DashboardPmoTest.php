<?php

namespace Tests\Feature\Dashboard;

use App\Models\PasienPmo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPmoTest extends TestCase
{
    use RefreshDatabase;

    public function test_pmo_dapat_membuka_dashboard(): void
    {
        $pmo = User::factory()->create(['role' => 'pmo', 'is_active' => true]);

        $pasien1 = User::factory()->create(['role' => 'pasien', 'is_active' => true]);
        $pasien2 = User::factory()->create(['role' => 'pasien', 'is_active' => true]);

        PasienPmo::factory()->create([
            'pmo_user_id' => $pmo->id,
            'id_user' => $pasien1->id,
            'is_active' => true,
        ]);
        PasienPmo::factory()->create([
            'pmo_user_id' => $pmo->id,
            'id_user' => $pasien2->id,
            'is_active' => true,
        ]);

        $this->actingAs($pmo)->get('/pmo/dashboard')
            ->assertOk()
            ->assertViewHas('total_pasien')
            ->assertViewHas('total_pasien', 2);
    }

    public function test_non_pmo_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->get('/pmo/dashboard')->assertForbidden();
    }
}
