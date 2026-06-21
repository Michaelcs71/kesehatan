<?php

namespace Tests\Feature\Pengaturan;

use App\Models\PengaturanPengingat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengaturanPengingatRouteTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        // Superadmin: User::hasPermissionTo override selalu true → lolos middleware.
        return User::factory()->create(['role' => 'superadmin']);
    }

    public function test_update_menyimpan_pengaturan(): void
    {
        $res = $this->actingAs($this->superadmin())->putJson(route('pengaturan-pengingat.update'), [
            'mo_aktif' => true,
            'mo_jumlah' => 5,
            'mo_interval_menit' => 20,
            'mo_pmo_mulai_ke' => 2,
            'cgd_aktif' => true,
            'cgd_dibuat_aktif' => false,
            'cgd_jam_h1' => '18:00',
        ]);

        $res->assertOk()->assertJson(['success' => true]);
        $this->assertSame(5, PengaturanPengingat::first()->mo_jumlah);
        $this->assertSame('18:00', PengaturanPengingat::first()->cgd_jam_h1);
    }

    public function test_validasi_pmo_mulai_ke_tidak_boleh_lebih_dari_jumlah(): void
    {
        $res = $this->actingAs($this->superadmin())->putJson(route('pengaturan-pengingat.update'), [
            'mo_aktif' => true,
            'mo_jumlah' => 3,
            'mo_interval_menit' => 15,
            'mo_pmo_mulai_ke' => 5, // > jumlah
            'cgd_aktif' => true,
            'cgd_dibuat_aktif' => true,
            'cgd_jam_h1' => '17:00',
        ]);

        $res->assertStatus(422)->assertJsonValidationErrors(['mo_pmo_mulai_ke']);
    }
}
