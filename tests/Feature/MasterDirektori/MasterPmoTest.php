<?php

namespace Tests\Feature\MasterDirektori;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterPmoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
    }

    public function test_admin_melihat_direktori_pmo(): void
    {
        User::factory()->create(['role' => 'pmo', 'is_active' => true, 'name' => 'Budi PMO']);

        $this->actingAs($this->admin())->get('/admin/master/pmo')
            ->assertOk()->assertSee('Budi PMO');
    }

    public function test_admin_melihat_detail_pmo(): void
    {
        $pmo = User::factory()->create(['role' => 'pmo', 'is_active' => true, 'name' => 'Budi PMO']);

        $this->actingAs($this->admin())->get("/admin/master/pmo/{$pmo->id}")
            ->assertOk()->assertSee('Budi PMO');
    }

    public function test_detail_non_pmo_404(): void
    {
        $bukanPmo = User::factory()->create(['role' => 'pasien', 'is_active' => true]);

        $this->actingAs($this->admin())->get("/admin/master/pmo/{$bukanPmo->id}")
            ->assertNotFound();
    }

    public function test_non_admin_ditolak(): void
    {
        $pasien = User::factory()->create(['role' => 'pasien', 'is_active' => true]);

        $this->actingAs($pasien)->get('/admin/master/pmo')->assertForbidden();
    }
}
