<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\JadwalCgd;
use App\Models\JadwalMinumObat;
use App\Models\MasterObat;
use App\Models\PasienPmo;
use App\Models\PengingatCgdLog;
use App\Models\PengingatMoLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Data dummy untuk Laporan Kepatuhan: pasien + PMO + jadwal + ~30 hari
 * log konfirmasi minum obat & cek gula darah dengan profil kepatuhan beragam.
 * Idempoten: dilewati bila log sudah ada.
 */
class LaporanDummySeeder extends Seeder
{
    private const HARI = 30;

    private const FOTO = 'seed/placeholder.jpg';

    public function run(): void
    {
        if (PengingatMoLog::count() > 0 || PengingatCgdLog::count() > 0) {
            $this->command->info('  [SKIP] Log pengingat sudah ada, seeder dummy laporan dilewati');

            return;
        }

        $obat = MasterObat::first();
        if (! $obat) {
            $this->command->warn('  [SKIP] Belum ada master obat, jalankan MasterObatSeeder dulu');

            return;
        }

        $pmo = User::where('role', UserRole::PMO->value)->first()
            ?? User::where('role', UserRole::ADMIN->value)->first();

        $admin = User::where('role', UserRole::SUPERADMIN->value)->first() ?? $pmo;

        // Jadwal CGD umum (dipakai semua log CGD)
        $jadwalCgd = JadwalCgd::create([
            'tgl_input' => Carbon::today()->subDays(self::HARI),
            'tgl_jadwal_cgd' => Carbon::today()->subDays(self::HARI),
            'jam_mulai' => '07:00',
            'jam_berakhir' => '09:00',
            'puasa' => 'Wajib',
            'tempat' => 'Puskesmas Sehat Sentosa',
            'status' => 'aktif',
            'created_by' => $admin?->id,
        ]);

        $patients = [
            ['email' => 'pasien@kesehatan.test',       'name' => 'Siti Pasien',   'jk' => 'P', 'profil' => 'baik'],
            ['email' => 'pasien.budi@kesehatan.test',  'name' => 'Budi Santoso',  'jk' => 'L', 'profil' => 'cukup'],
            ['email' => 'pasien.rina@kesehatan.test',  'name' => 'Rina Wijaya',   'jk' => 'P', 'profil' => 'kurang'],
            ['email' => 'pasien.agus@kesehatan.test',  'name' => 'Agus Setiawan', 'jk' => 'L', 'profil' => 'baik'],
        ];

        $totalMo = 0;
        $totalCgd = 0;

        foreach ($patients as $i => $p) {
            $user = $this->ensurePasien($p, $i);

            $pasienPmo = PasienPmo::firstOrCreate(
                ['id_user' => $user->id],
                [
                    'pmo_user_id' => $pmo?->id,
                    'nama_pasien' => $p['name'],
                    'nik' => str_pad((string) (3201000000000000 + $i), 16, '0'),
                    'nama_pmo' => $pmo?->name ?? 'PMO',
                    'jenis_pmo' => 'Kader',
                    'tanggal_regis' => Carbon::today()->subMonths(6),
                    'status_diabetes' => ['baik' => 'Rendah', 'cukup' => 'Sedang', 'kurang' => 'Tinggi'][$p['profil']],
                    'is_active' => true,
                    'created_by' => $admin?->id,
                ]
            );

            $jadwalMo = JadwalMinumObat::create([
                'id_pasien_pmo' => $pasienPmo->id,
                'obat_id' => $obat->id,
                'nama_pasien' => $p['name'],
                'nama_pmo' => $pmo?->name ?? 'PMO',
                'tgl_mulai' => Carbon::today()->subDays(self::HARI),
                'jam_mulai' => '08:00',
                'frekuensi_per_hari' => 1,
                'status' => 'aktif',
                'created_by' => $admin?->id,
            ]);

            $totalMo += $this->seedMoLogs($jadwalMo, $user, $p, $obat, $admin);
            $totalCgd += $this->seedCgdLogs($jadwalCgd, $user, $p, $admin);
        }

        $this->command->info("  [OK] Dummy laporan: {$totalMo} log MO, {$totalCgd} log CGD untuk ".count($patients).' pasien');
    }

    private function ensurePasien(array $p, int $i): User
    {
        return User::updateOrCreate(
            ['email' => $p['email']],
            [
                'name' => $p['name'],
                'username' => 'pasien_'.strtolower(explode(' ', $p['name'])[0]).$i,
                'password' => Hash::make('password'),
                'role' => UserRole::PASIEN->value,
                'whatsapp_number' => '0812'.str_pad((string) (10000000 + $i), 8, '0'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }

    private function seedMoLogs(JadwalMinumObat $jadwal, User $user, array $p, MasterObat $obat, ?User $admin): int
    {
        $target = '08:00';
        $count = 0;

        for ($d = self::HARI - 1; $d >= 0; $d--) {
            $tgl = Carbon::today()->subDays($d);
            $menit = $this->randPatuhMenit($p['profil']);
            $jam = Carbon::createFromFormat('H:i', $target)->addMinutes($menit)->format('H:i:s');

            PengingatMoLog::create([
                'id_jo' => $jadwal->id,
                'id_user' => $user->id,
                'nama_pasien' => $p['name'],
                'nama_obat' => $obat->nama,
                'tgl_minum_obat' => $tgl->toDateString(),
                'jam_minum_obat' => $jam,
                'jam_slot_target' => $target.':00',
                'patuh_menit' => $menit,
                'foto_obat' => self::FOTO,
                'status' => 'aktif',
                'created_by' => $admin?->id,
            ]);
            $count++;
        }

        return $count;
    }

    private function seedCgdLogs(JadwalCgd $jadwal, User $user, array $p, ?User $admin): int
    {
        $count = 0;

        // Cek tiap 4 hari
        for ($d = self::HARI - 1; $d >= 0; $d -= 4) {
            $tgl = Carbon::today()->subDays($d);
            $hasil = $this->randHasilMgdl($p['profil']);
            $kat = PengingatCgdLog::determineKategori($hasil);

            PengingatCgdLog::create([
                'id_cgd' => $jadwal->id,
                'id_user' => $user->id,
                'nama_pasien' => $p['name'],
                'jenis_kelamin' => $p['jk'],
                'tempat_cgd' => 'Puskesmas Sehat Sentosa',
                'tgl_cgd' => $tgl->toDateString(),
                'jam_cgd' => '07:30:00',
                'hasil_mgdl' => $hasil,
                'kategori_hasil' => $kat,
                'patuh_selisih' => PengingatCgdLog::calculatePatuhSelisih($hasil, $p['jk']),
                'foto_layar' => self::FOTO,
                'status' => 'aktif',
                'created_by' => $admin?->id,
            ]);
            $count++;
        }

        return $count;
    }

    /** Selisih menit dari jadwal, distribusi sesuai profil kepatuhan. */
    private function randPatuhMenit(string $profil): int
    {
        $r = rand(1, 100);
        [$pTepat, $pTelat] = match ($profil) {
            'baik' => [85, 97],
            'cukup' => [60, 88],
            default => [35, 70], // kurang
        };

        if ($r <= $pTepat) {
            return rand(-15, 15);
        }
        if ($r <= $pTelat) {
            return rand(16, 60) * (rand(0, 1) ? 1 : -1);
        }

        return rand(61, 150);
    }

    /** Hasil gula darah (mg/dL), distribusi sesuai profil. */
    private function randHasilMgdl(string $profil): int
    {
        $r = rand(1, 100);

        return match ($profil) {
            'baik' => $r <= 70 ? rand(90, 140) : ($r <= 90 ? rand(141, 199) : rand(200, 250)),
            'cukup' => $r <= 40 ? rand(95, 140) : ($r <= 75 ? rand(141, 199) : rand(200, 290)),
            default => $r <= 20 ? rand(110, 140) : ($r <= 50 ? rand(141, 199) : ($r <= 85 ? rand(200, 299) : rand(300, 380))),
        };
    }
}
