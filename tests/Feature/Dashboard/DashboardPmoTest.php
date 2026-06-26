<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPmoTest extends TestCase
{
    use RefreshDatabase;

    public function test_pmo_dapat_membuka_dashboard(): void
    {
        $pmo = User::factory()->create(['role' => 'pmo', 'is_active' => true]);

        $this->actingAs($pmo)->get('/pmo/dashboard')
            ->assertOk()
            ->assertViewHas('total_pasien', 0);
    }
}
