<?php

namespace Tests\Feature\Impersonation;

use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private function buatUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    public function test_superadmin_bisa_mulai_pov_pasien(): void
    {
        $super = $this->buatUser('superadmin');
        $pasien = $this->buatUser('pasien');

        $res = $this->actingAs($super)->post('/impersonate/pasien');

        $res->assertRedirect(route('pasien.dashboard'));
        $this->assertAuthenticatedAs($pasien);
        $this->assertSame($super->id, session(ImpersonationService::SESSION_KEY));
    }

    public function test_non_superadmin_tidak_bisa_mulai(): void
    {
        $admin = $this->buatUser('admin');
        $this->buatUser('pasien');

        $res = $this->actingAs($admin)->post('/impersonate/pasien');

        $res->assertForbidden();
        $this->assertAuthenticatedAs($admin); // sesi tak berubah
    }

    public function test_role_superadmin_ditolak_oleh_route(): void
    {
        $super = $this->buatUser('superadmin');

        $res = $this->actingAs($super)->post('/impersonate/superadmin');

        $res->assertNotFound(); // regex route hanya admin|pmo|pasien
    }

    public function test_service_menolak_role_invalid(): void
    {
        $super = $this->buatUser('superadmin');
        $this->expectException(\InvalidArgumentException::class);

        ImpersonationService::mulaiSebagai($super, 'superadmin');
    }

    public function test_wakil_kosong_redirect_dengan_error(): void
    {
        $super = $this->buatUser('superadmin');
        // tidak ada user pmo

        $res = $this->actingAs($super)->post('/impersonate/pmo');

        $res->assertRedirect();
        $res->assertSessionHas('error');
        $this->assertAuthenticatedAs($super);
        $this->assertNull(session(ImpersonationService::SESSION_KEY));
    }

    public function test_kembali_memulihkan_superadmin(): void
    {
        $super = $this->buatUser('superadmin');
        $pasien = $this->buatUser('pasien');

        $this->actingAs($super)->post('/impersonate/pasien');
        $this->assertAuthenticatedAs($pasien);

        $res = $this->post('/impersonate/leave');

        $res->assertRedirect(route('superadmin.dashboard'));
        $this->assertAuthenticatedAs($super);
        $this->assertNull(session(ImpersonationService::SESSION_KEY));
    }

    public function test_superadmin_kedua_juga_bisa(): void
    {
        $this->buatUser('superadmin'); // superadmin pertama
        $super2 = $this->buatUser('superadmin');
        $pasien = $this->buatUser('pasien');

        $res = $this->actingAs($super2)->post('/impersonate/pasien');

        $res->assertRedirect(route('pasien.dashboard'));
        $this->assertAuthenticatedAs($pasien);
    }

    public function test_kembali_saat_superadmin_asal_terhapus_logout_aman(): void
    {
        $super = $this->buatUser('superadmin');
        $this->buatUser('pasien');

        $this->actingAs($super)->post('/impersonate/pasien');

        // superadmin asal terhapus di tengah sesi POV
        User::where('id', $super->id)->delete();

        $res = $this->post('/impersonate/leave');

        $res->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertNull(session(ImpersonationService::SESSION_KEY));
    }

    public function test_bisa_pindah_role_langsung_saat_pov(): void
    {
        $super = $this->buatUser('superadmin');
        $this->buatUser('pasien');
        $admin = $this->buatUser('admin');

        // mulai POV sebagai pasien
        $this->actingAs($super)->post('/impersonate/pasien');

        // pindah langsung ke admin tanpa kembali ke superadmin
        $res = $this->post('/impersonate/admin');

        $res->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
        // titik kembali tetap superadmin asli, bukan pasien
        $this->assertSame($super->id, session(ImpersonationService::SESSION_KEY));
    }
}
