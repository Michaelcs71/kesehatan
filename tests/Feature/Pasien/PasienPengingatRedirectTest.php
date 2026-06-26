<?php

namespace Tests\Feature\Pasien;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasienPengingatRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengingat_mo_redirect_ke_riwayat_tab_obat(): void
    {
        $p = User::factory()->create(['role' => 'pasien', 'is_active' => true]);
        $this->actingAs($p)->get('/pasien/pengingat-mo')
            ->assertRedirect(route('pasien.riwayat', ['tab' => 'obat']));
    }

    public function test_pengingat_cgd_redirect_ke_riwayat_tab_gula(): void
    {
        $p = User::factory()->create(['role' => 'pasien', 'is_active' => true]);
        $this->actingAs($p)->get('/pasien/pengingat-cgd')
            ->assertRedirect(route('pasien.riwayat', ['tab' => 'gula']));
    }
}
