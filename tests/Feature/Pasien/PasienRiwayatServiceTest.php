<?php

namespace Tests\Feature\Pasien;

use App\Models\JadwalCgd;
use App\Models\JadwalCgdPeserta;
use App\Models\JadwalMinumObat;
use App\Models\PasienPmo;
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

    public function test_jadwal_cgd_hanya_milik_pasien(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-26 10:00:00'));
        $p = $this->pasien();
        $pp = $this->pasienPmoUntuk($p);

        // Pasien pertama: 1 mendatang + 1 lewat
        $besok = JadwalCgd::factory()->create(['tgl_jadwal_cgd' => '2026-06-27', 'jam_mulai' => '08:00:00']);
        $kemarin = JadwalCgd::factory()->create(['tgl_jadwal_cgd' => '2026-06-25', 'jam_mulai' => '09:00:00']);
        foreach ([$besok, $kemarin] as $j) {
            JadwalCgdPeserta::create([
                'jadwal_cgd_id' => $j->id, 'id_pasien_pmo' => $pp->id,
                'nama_pasien' => $p->name, 'nama_pmo' => '-',
            ]);
        }

        // Pasien kedua: tidak boleh muncul di hasil pasien pertama
        $p2 = $this->pasien();
        $pp2 = $this->pasienPmoUntuk($p2);
        $jadwalLain = JadwalCgd::factory()->create(['tgl_jadwal_cgd' => '2026-06-28', 'jam_mulai' => '07:00:00']);
        JadwalCgdPeserta::create([
            'jadwal_cgd_id' => $jadwalLain->id, 'id_pasien_pmo' => $pp2->id,
            'nama_pasien' => $p2->name, 'nama_pmo' => '-',
        ]);

        $hasil = PasienRiwayatService::jadwalCgdPasien($p->id);

        $this->assertCount(1, $hasil['mendatang']);
        $this->assertCount(1, $hasil['lewat']);
        $total = count($hasil['mendatang']) + count($hasil['lewat']);
        $this->assertSame(2, $total, 'Jadwal CGD pasien lain tidak boleh ikut terhitung');
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

        // Pasien kedua dengan STATUS_MENUNGGU sendiri — tidak boleh ikut terhitung
        $p2 = $this->pasien();
        PengingatKejadian::create([
            'jenis' => 'mo', 'jadwal_id' => Str::uuid()->toString(), 'id_pasien_pmo' => null,
            'user_pasien_id' => $p2->id, 'user_pmo_id' => null,
            'waktu_jadwal' => now(), 'status' => PengingatKejadian::STATUS_MENUNGGU,
        ]);

        $this->assertCount(1, PasienRiwayatService::pendingKonfirmasi($p->id));
    }
}

// Helper: id_jo NOT NULL — buat jadwal MO via factory dan kembalikan id-nya.
function JadwalMinumObatId(): string
{
    return JadwalMinumObat::factory()->create()->id;
}
