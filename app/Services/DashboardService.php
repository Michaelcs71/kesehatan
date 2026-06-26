<?php

namespace App\Services;

use App\Models\Edukasi;
use App\Models\PasienPmo;
use App\Models\PengingatKejadian;
use App\Models\Pengumuman;
use App\Models\User;
use App\Repos\DashboardRepository;
use Illuminate\Support\Carbon;

class DashboardService
{
    /**
     * Kumpulkan semua data yang dibutuhkan dashboard pasien.
     */
    public static function untukPasien(User $pasien): array
    {
        $mo = DashboardRepository::kejadianMoHariIni($pasien->id);
        $cgd = DashboardRepository::cgdHariIni($pasien->id);

        return [
            'obat_hari_ini' => $mo['total'],
            'obat_selesai' => $mo['selesai'],
            'cgd_hari_ini' => $cgd['total'],
            'cgd_selesai' => $cgd['selesai'],
            'kepatuhan' => DashboardRepository::hitungKepatuhanMo($pasien->id),
            'streak' => DashboardRepository::hitungStreak($pasien->id),
            'jadwal_hari_ini' => self::jadwalHariIniPasien($pasien->id),
            'gd_trend' => DashboardRepository::trenGdPasien($pasien->id),
            'pmo' => self::infoPmo($pasien->id),
            'pengumuman' => self::pengumumanTerbaru(),
            'tips' => self::tips(),
        ];
    }

    /**
     * Daftar kejadian hari ini (MO + CGD) diurutkan berdasarkan waktu.
     */
    private static function jadwalHariIniPasien(string $pasienId): array
    {
        return PengingatKejadian::query()
            ->where('user_pasien_id', $pasienId)
            ->whereDate('waktu_jadwal', Carbon::today())
            ->orderBy('waktu_jadwal')
            ->get()
            ->map(fn ($k) => [
                'waktu' => Carbon::parse($k->waktu_jadwal)->format('H:i'),
                'jenis' => $k->jenis,
                'status' => match ($k->status) {
                    PengingatKejadian::STATUS_DIKONFIRMASI => 'done',
                    PengingatKejadian::STATUS_TERLEWAT => 'missed',
                    default => 'upcoming',
                },
            ])->all();
    }

    /**
     * Info PMO aktif untuk pasien. WhatsApp diambil dari users.whatsapp_number (bukan UserBiodata).
     */
    private static function infoPmo(string $pasienId): ?array
    {
        $pp = PasienPmo::query()->forPasien($pasienId)->active()->with('pmo')->first();
        if (! $pp) {
            return null;
        }

        return [
            'nama' => $pp->nama_pmo,
            'jenis' => $pp->jenis_pmo,
            'whatsapp' => $pp->pmo?->whatsapp_number ?? null,
        ];
    }

    /**
     * Pengumuman terbaru yang sudah diterbitkan.
     */
    private static function pengumumanTerbaru(int $limit = 3): array
    {
        return Pengumuman::query()->published()->latest('published_at')->limit($limit)->get()
            ->map(fn ($p) => [
                'title' => $p->judul,
                'meta' => optional($p->published_at)->translatedFormat('d M Y'),
            ])->all();
    }

    /**
     * Tips edukasi: ambil dari tabel edukasi bila ada, fallback ke tips statis.
     */
    private static function tips(): array
    {
        $edukasi = Edukasi::query()->published()->latest('published_at')->limit(4)->get();
        if ($edukasi->isNotEmpty()) {
            return $edukasi->map(fn ($e) => ['icon' => '📖', 'text' => $e->judul])->all();
        }

        return [
            ['icon' => '💧', 'text' => 'Minum air putih minimal 8 gelas per hari.'],
            ['icon' => '🥗', 'text' => 'Pilih karbohidrat kompleks: nasi merah, oat, atau ubi.'],
            ['icon' => '🚶', 'text' => 'Jalan kaki 30 menit setelah makan menurunkan gula darah.'],
        ];
    }
}
