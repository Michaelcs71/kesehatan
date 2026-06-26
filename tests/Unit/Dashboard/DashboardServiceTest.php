<?php

namespace Tests\Unit\Dashboard;

use App\Models\JadwalMinumObat;
use App\Models\PengingatKejadian;
use App\Models\PengingatMoLog;
use App\Models\User;
use App\Repos\DashboardRepository;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
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

    private function buatMoLog(User $pasien, int $patuhMenit): void
    {
        $jo = JadwalMinumObat::factory()->create();
        PengingatMoLog::create([
            'id_jo' => $jo->id,
            'id_user' => $pasien->id,
            'nama_pasien' => 'A',
            'nama_obat' => 'O',
            'tgl_minum_obat' => now()->toDateString(),
            'jam_minum_obat' => '08:00:00',
            'jam_slot_target' => '08:00:00',
            'patuh_menit' => $patuhMenit,
            'foto_obat' => 'foto.jpg',
            'status' => 'aktif',
        ]);
    }

    public function test_kepatuhan_menghitung_persen_tepat_waktu(): void
    {
        $p = $this->pasien();
        // 3 tepat (<=15), 1 telat (>15) => 75%
        foreach ([5, -10, 0] as $menit) {
            $this->buatMoLog($p, $menit);
        }
        $this->buatMoLog($p, 60);

        $this->assertSame(75, DashboardRepository::hitungKepatuhanMo($p->id));
    }

    public function test_kepatuhan_nol_saat_belum_ada_log(): void
    {
        $this->assertSame(0, DashboardRepository::hitungKepatuhanMo($this->pasien()->id));
    }

    public function test_streak_terputus_oleh_terlewat(): void
    {
        $p = $this->pasien();
        Carbon::setTestNow(Carbon::parse('2026-06-26 10:00:00'));
        // hari ini & kemarin tidak terlewat, lusa terlewat => streak 2
        $jadwalId = Str::uuid()->toString();
        PengingatKejadian::create([
            'jenis' => 'mo',
            'jadwal_id' => $jadwalId,
            'id_pasien_pmo' => null,
            'user_pasien_id' => $p->id,
            'user_pmo_id' => null,
            'waktu_jadwal' => '2026-06-26 08:00:00',
            'status' => 'dikonfirmasi',
        ]);
        PengingatKejadian::create([
            'jenis' => 'mo',
            'jadwal_id' => Str::uuid()->toString(),
            'id_pasien_pmo' => null,
            'user_pasien_id' => $p->id,
            'user_pmo_id' => null,
            'waktu_jadwal' => '2026-06-25 08:00:00',
            'status' => 'dikonfirmasi',
        ]);
        PengingatKejadian::create([
            'jenis' => 'mo',
            'jadwal_id' => Str::uuid()->toString(),
            'id_pasien_pmo' => null,
            'user_pasien_id' => $p->id,
            'user_pmo_id' => null,
            'waktu_jadwal' => '2026-06-24 08:00:00',
            'status' => 'terlewat',
        ]);

        $this->assertSame(2, DashboardRepository::hitungStreak($p->id));
    }

    public function test_streak_tidak_melampaui_tanggal_paling_awal_kejadian(): void
    {
        $p = $this->pasien();
        Carbon::setTestNow(Carbon::parse('2026-06-26 10:00:00'));
        // Pasien punya kejadian hanya 3 hari terakhir, semua dikonfirmasi — streak harus 3, bukan 366
        foreach (['2026-06-24', '2026-06-25', '2026-06-26'] as $tgl) {
            PengingatKejadian::create([
                'jenis' => 'mo',
                'jadwal_id' => Str::uuid()->toString(),
                'id_pasien_pmo' => null,
                'user_pasien_id' => $p->id,
                'user_pmo_id' => null,
                'waktu_jadwal' => $tgl.' 08:00:00',
                'status' => 'dikonfirmasi',
            ]);
        }

        $this->assertSame(3, DashboardRepository::hitungStreak($p->id));
    }

    public function test_untuk_pasien_mengembalikan_struktur_lengkap(): void
    {
        $vm = DashboardService::untukPasien($this->pasien());

        $this->assertArrayHasKey('obat_hari_ini', $vm);
        $this->assertArrayHasKey('obat_selesai', $vm);
        $this->assertArrayHasKey('cgd_hari_ini', $vm);
        $this->assertArrayHasKey('cgd_selesai', $vm);
        $this->assertArrayHasKey('kepatuhan', $vm);
        $this->assertArrayHasKey('streak', $vm);
        $this->assertArrayHasKey('jadwal_hari_ini', $vm);
        $this->assertArrayHasKey('gd_trend', $vm);
        $this->assertArrayHasKey('pmo', $vm);
        $this->assertArrayHasKey('pengumuman', $vm);
        $this->assertArrayHasKey('tips', $vm);
        $this->assertSame(0, $vm['kepatuhan']);
        $this->assertSame([], $vm['jadwal_hari_ini']);
    }
}
