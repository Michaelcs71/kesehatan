<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_membuka_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertViewHas('total_obat', 0);
    }

    public function test_superadmin_dapat_membuka_dashboard(): void
    {
        $su = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);

        $this->actingAs($su)->get('/superadmin/dashboard')
            ->assertOk()
            ->assertViewHas('ringkasan_user');
    }
}
