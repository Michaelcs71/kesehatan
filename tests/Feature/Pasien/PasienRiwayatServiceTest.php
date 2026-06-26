<?php

namespace Tests\Feature\Pasien;

use App\Models\JadwalCgd;
use App\Models\JadwalCgdPeserta;
use App\Models\PasienPmo;
use App\Models\PengingatCgdLog;
use App\Models\PengingatKejadian;
use App\Models\PengingatMoLog;
use App\Models\User;
use App\Services\PasienRiwayatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasienRiwayatServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pasien(): User
    {
        return User::factory()->create(['role' => 'pasien', 'is_active' => true]);
    }

    private function pasienPmoUntuk(User $p): PasienPmo
    {
        return PasienPmo::create([
            'id_user' => $p->id, 'pmo_user_id' => null,
            'nama_pasien' => $p->name, 'nik' => fake()->numerify('################'),
            'nama_pmo' => '-', 'jenis_pmo' => 'Keluarga',
            'tanggal_regis' => now()->toDateString(), 'status_diabetes' => 'Tipe 2',
            'is_active' => true,
        ]);
    }

    public function test_jadwal_cgd_memisah_mendatang_dan_lewat(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-26 10:00:00'));
        $p = $this->pasien();
        $pp = $this->pasienPmoUntuk($p);

        $besok = JadwalCgd::factory()->create(['tgl_jadwal_cgd' => '2026-06-27', 'jam_mulai' => '08:00:00']);
        $kemarin = JadwalCgd::factory()->create(['tgl_jadwal_cgd' => '2026-06-25', 'jam_mulai' => '09:00:00']);
        foreach ([$besok, $kemarin] as $j) {
            JadwalCgdPeserta::create([
                'jadwal_cgd_id' => $j->id, 'id_pasien_pmo' => $pp->id,
                'nama_pasien' => $p->name, 'nama_pmo' => '-',
            ]);
        }

        $hasil = PasienRiwayatService::jadwalCgdPasien($p->id);

        $this->assertCount(1, $hasil['mendatang']);
        $this->assertCount(1, $hasil['lewat']);
        $this->assertSame('08:00', $hasil['mendatang'][0]['jam']);
    }

    public function test_riwayat_mo_hanya_milik_pasien(): void
    {
        $p = $this->pasien();
        $lain = $this->pasien();
        PengingatMoLog::create([
            'id_jo' => JadwalMinumObatId(), 'id_user' => $p->id, 'nama_pasien' => $p->name,
            'nama_obat' => 'Metformin', 'tgl_minum_obat' => now()->toDateString(),
            'jam_minum_obat' => '08:00:00', 'jam_slot_target' => '08:00:00',
            'patuh_menit' => 0, 'foto_obat' => 'x.jpg', 'status' => 'aktif',
        ]);
        PengingatMoLog::create([
            'id_jo' => JadwalMinumObatId(), 'id_user' => $lain->id, 'nama_pasien' => $lain->name,
            'nama_obat' => 'Lain', 'tgl_minum_obat' => now()->toDateString(),
            'jam_minum_obat' => '08:00:00', 'jam_slot_target' => '08:00:00',
            'patuh_menit' => 0, 'foto_obat' => 'x.jpg', 'status' => 'aktif',
        ]);

        $page = PasienRiwayatService::riwayatMo($p->id);

        $this->assertSame(1, $page->total());
        $this->assertSame($p->id, $page->first()->id_user);
    }

    public function test_pending_konfirmasi_hanya_kejadian_mo_menunggu_milik_pasien(): void
    {
        $p = $this->pasien();
        PengingatKejadian::create([
            'jenis' => 'mo', 'jadwal_id' => Str::uuid()->toString(), 'id_pasien_pmo' => null,
            'user_pasien_id' => $p->id, 'user_pmo_id' => null,
            'waktu_jadwal' => now(), 'status' => PengingatKejadian::STATUS_MENUNGGU,
        ]);
        PengingatKejadian::create([
            'jenis' => 'mo', 'jadwal_id' => Str::uuid()->toString(), 'id_pasien_pmo' => null,
            'user_pasien_id' => $p->id, 'user_pmo_id' => null,
            'waktu_jadwal' => now(), 'status' => PengingatKejadian::STATUS_DIKONFIRMASI,
        ]);

        $this->assertCount(1, PasienRiwayatService::pendingKonfirmasi($p->id));
    }
}

// Helper: id_jo NOT NULL — buat jadwal MO via factory dan kembalikan id-nya.
function JadwalMinumObatId(): string
{
    return \App\Models\JadwalMinumObat::factory()->create()->id;
}
