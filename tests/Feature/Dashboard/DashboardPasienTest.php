<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPasienTest extends TestCase
{
    use RefreshDatabase;

    public function test_pasien_dapat_membuka_dashboard(): void
    {
        $pasien = User::factory()->create(['role' => 'pasien', 'is_active' => true]);

        $this->actingAs($pasien)->get('/pasien/dashboard')
            ->assertOk()
            ->assertViewHas('kepatuhan', 0);
    }

    public function test_non_pasien_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->get('/pasien/dashboard')->assertForbidden();
    }
}
