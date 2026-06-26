<?php

namespace Tests\Feature\Pasien;

use App\Models\JadwalCgd;
use App\Models\JadwalMinumObat;
use App\Models\PengingatCgdLog;
use App\Models\PengingatKejadian;
use App\Models\PengingatMoLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasienRiwayatTest extends TestCase
{
    use RefreshDatabase;

    private function pasien(): User
    {
        return User::factory()->create(['role' => 'pasien', 'is_active' => true]);
    }

    public function test_tab_obat_menampilkan_log_mo_pasien(): void
    {
        $p = $this->pasien();
        PengingatMoLog::create([
            'id_jo' => JadwalMinumObat::factory()->create()->id,
            'id_user' => $p->id, 'nama_pasien' => $p->name, 'nama_obat' => 'Metformin500',
            'tgl_minum_obat' => now()->toDateString(), 'jam_minum_obat' => '08:00:00',
            'jam_slot_target' => '08:00:00', 'patuh_menit' => 0, 'foto_obat' => 'x.jpg', 'status' => 'aktif',
        ]);

        $this->actingAs($p)->get('/pasien/riwayat?tab=obat')
            ->assertOk()->assertSee('Metformin500');
    }

    public function test_tab_gula_menampilkan_log_cgd_pasien(): void
    {
        $p = $this->pasien();
        PengingatCgdLog::create([
            'id_cgd' => JadwalCgd::factory()->create()->id,
            'id_user' => $p->id, 'nama_pasien' => $p->name, 'jenis_kelamin' => 'L',
            'tempat_cgd' => 'Klinik X', 'tgl_cgd' => now()->toDateString(), 'jam_cgd' => '07:00:00',
            'hasil_mgdl' => 123, 'kategori_hasil' => 'normal', 'patuh_selisih' => -77,
            'foto_layar' => 'y.jpg', 'status' => 'aktif',
        ]);

        $this->actingAs($p)->get('/pasien/riwayat?tab=gula')
            ->assertOk()->assertSee('123');
    }

    public function test_banner_konfirmasi_muncul_dan_menaut_konfirmasi(): void
    {
        $p = $this->pasien();
        $k = PengingatKejadian::create([
            'jenis' => 'mo', 'jadwal_id' => Str::uuid()->toString(), 'id_pasien_pmo' => null,
            'user_pasien_id' => $p->id, 'user_pmo_id' => null,
            'waktu_jadwal' => now(), 'status' => PengingatKejadian::STATUS_MENUNGGU,
        ]);

        $this->actingAs($p)->get('/pasien/riwayat')
            ->assertOk()
            ->assertSee(route('pengingat.konfirmasi.show', $k->id), false);
    }

    public function test_non_pasien_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->get('/pasien/riwayat')->assertForbidden();
    }
}
