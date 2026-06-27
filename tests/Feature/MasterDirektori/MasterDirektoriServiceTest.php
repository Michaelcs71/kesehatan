<?php

namespace Tests\Feature\MasterDirektori;

use App\Models\PasienPmo;
use App\Models\User;
use App\Services\MasterDirektoriService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDirektoriServiceTest extends TestCase
{
    use RefreshDatabase;

    private function pasienDenganPmo(string $nama, ?string $nik = null): User
    {
        $p = User::factory()->create(['role' => 'pasien', 'is_active' => true]);
        PasienPmo::create([
            'id_user' => $p->id, 'pmo_user_id' => null, 'nama_pasien' => $nama,
            'nik' => $nik ?? fake()->numerify('################'), 'nama_pmo' => '-',
            'jenis_pmo' => 'Keluarga', 'tanggal_regis' => now()->toDateString(),
            'status_diabetes' => 'Tipe 2', 'is_active' => true,
        ]);

        return $p;
    }

    public function test_daftar_pasien_memetakan_kolom_dan_kepatuhan(): void
    {
        $this->pasienDenganPmo('Siti Aminah');

        $page = MasterDirektoriService::daftarPasien();

        $this->assertSame(1, $page->total());
        $row = $page->items()[0];
        $this->assertSame('Siti Aminah', $row['nama']);
        $this->assertArrayHasKey('kepatuhan', $row);
        $this->assertSame(0, $row['kepatuhan']); // belum ada log
    }

    public function test_daftar_pasien_menghormati_cari(): void
    {
        $this->pasienDenganPmo('Siti Aminah');
        $this->pasienDenganPmo('Budi Santoso');

        $page = MasterDirektoriService::daftarPasien(['cari' => 'Budi']);

        $this->assertSame(1, $page->total());
        $this->assertSame('Budi Santoso', $page->items()[0]['nama']);
    }

    public function test_detail_pasien_null_untuk_non_pasien(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->assertNull(MasterDirektoriService::detailPasien($admin->id));
    }

    public function test_detail_pasien_mengembalikan_struktur(): void
    {
        $p = $this->pasienDenganPmo('Siti Aminah');

        $d = MasterDirektoriService::detailPasien($p->id);

        $this->assertSame('Siti Aminah', $d['nama']);
        $this->assertArrayHasKey('kepatuhan', $d);
        $this->assertArrayHasKey('jadwal_mo', $d);
        $this->assertArrayHasKey('jadwal_cgd', $d);
        $this->assertArrayHasKey('riwayat_mo', $d);
        $this->assertArrayHasKey('riwayat_cgd', $d);
    }

    public function test_daftar_pmo_menghitung_binaan(): void
    {
        $pmo = User::factory()->create(['role' => 'pmo', 'is_active' => true]);
        $p = User::factory()->create(['role' => 'pasien', 'is_active' => true]);
        PasienPmo::create([
            'id_user' => $p->id, 'pmo_user_id' => $pmo->id, 'nama_pasien' => 'Pasien A',
            'nik' => fake()->numerify('################'), 'nama_pmo' => $pmo->name,
            'jenis_pmo' => 'Keluarga', 'tanggal_regis' => now()->toDateString(),
            'status_diabetes' => 'Tipe 2', 'is_active' => true,
        ]);

        $page = MasterDirektoriService::daftarPmo();

        $this->assertSame(1, $page->total());
        $this->assertSame(1, $page->items()[0]['jumlah_binaan']);
    }

    public function test_detail_pmo_null_untuk_non_pmo(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->assertNull(MasterDirektoriService::detailPmo($admin->id));
    }

    public function test_detail_pmo_berisi_binaan(): void
    {
        $pmo = User::factory()->create(['role' => 'pmo', 'is_active' => true]);
        $p = User::factory()->create(['role' => 'pasien', 'is_active' => true]);
        PasienPmo::create([
            'id_user' => $p->id, 'pmo_user_id' => $pmo->id, 'nama_pasien' => 'Pasien A',
            'nik' => fake()->numerify('################'), 'nama_pmo' => $pmo->name,
            'jenis_pmo' => 'Keluarga', 'tanggal_regis' => now()->toDateString(),
            'status_diabetes' => 'Tipe 2', 'is_active' => true,
        ]);

        $d = MasterDirektoriService::detailPmo($pmo->id);

        $this->assertSame($pmo->name, $d['nama']);
        $this->assertCount(1, $d['binaan']);
        $this->assertSame('Pasien A', $d['binaan'][0]['nama']);
    }
}
