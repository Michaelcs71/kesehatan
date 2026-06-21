<?php

namespace Tests\Feature\Pengaturan;

use App\Models\PengaturanPengingat;
use App\Models\User;
use App\Services\PengaturanPengingatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengaturanPengingatServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_mengembalikan_default_saat_tabel_kosong(): void
    {
        $s = PengaturanPengingatService::get();

        $this->assertSame(4, $s->mo_jumlah);
        $this->assertSame(15, $s->mo_interval_menit);
        $this->assertSame(3, $s->mo_pmo_mulai_ke);
        $this->assertTrue($s->mo_aktif);
        $this->assertTrue($s->cgd_aktif);
        $this->assertTrue($s->cgd_dibuat_aktif);
        $this->assertSame('17:00', $s->cgd_jam_h1);
        $this->assertSame(0, PengaturanPengingat::count()); // get() tidak menyimpan
    }

    public function test_update_menyimpan_dan_stamp_updated_by(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $s = PengaturanPengingatService::update([
            'mo_aktif' => true,
            'mo_jumlah' => 6,
            'mo_interval_menit' => 20,
            'mo_pmo_mulai_ke' => 4,
            'cgd_aktif' => false,
            'cgd_dibuat_aktif' => false,
            'cgd_jam_h1' => '18:30',
        ]);

        $this->assertSame(1, PengaturanPengingat::count());
        $this->assertSame(6, $s->fresh()->mo_jumlah);
        $this->assertFalse($s->fresh()->cgd_aktif);
        $this->assertSame($admin->id, $s->fresh()->updated_by);

        // update kedua tidak membuat baris baru
        PengaturanPengingatService::update(['mo_jumlah' => 7] + $s->toArray());
        $this->assertSame(1, PengaturanPengingat::count());
    }
}
