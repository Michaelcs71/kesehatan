<?php

namespace App\Repos;

use App\Models\JadwalCgdPeserta;
use App\Models\PasienPmo;
use App\Models\PengingatCgdLog;
use App\Models\PengingatKejadian;
use App\Models\PengingatMoLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardRepository
{
    /**
     * Hitung persentase kepatuhan minum obat dalam N hari terakhir.
     * Tepat waktu = ABS(patuh_menit) <= 15. Denominator 0 → 0.
     */
    public static function hitungKepatuhanMo(string $pasienId, int $hari = 30): int
    {
        $sejak = Carbon::today()->subDays($hari - 1)->toDateString();

        $total = PengingatMoLog::query()->forUser($pasienId)
            ->where('tgl_minum_obat', '>=', $sejak)->count();

        if ($total === 0) {
            return 0;
        }

        $tepat = PengingatMoLog::query()->forUser($pasienId)
            ->where('tgl_minum_obat', '>=', $sejak)
            ->whereRaw('ABS(patuh_menit) <= 15')->count();

        return (int) round($tepat / $total * 100);
    }

    /**
     * Hitung streak: berapa hari berturut-turut dari hari ini yang tidak punya kejadian terlewat,
     * dibatasi sejak tanggal paling awal kejadian pasien.
     * Tidak ada riwayat sama sekali → 0.
     */
    public static function hitungStreak(string $pasienId): int
    {
        $earliestWaktu = PengingatKejadian::query()
            ->where('user_pasien_id', $pasienId)
            ->min('waktu_jadwal');

        if ($earliestWaktu === null) {
            return 0;
        }

        $earliestDate = Carbon::parse($earliestWaktu)->toDateString();

        $terlewat = PengingatKejadian::query()
            ->where('user_pasien_id', $pasienId)
            ->where('status', PengingatKejadian::STATUS_TERLEWAT)
            ->pluck('waktu_jadwal')
            ->map(fn ($w) => Carbon::parse($w)->toDateString())
            ->unique()->flip();

        $streak = 0;
        $tanggal = Carbon::today();
        while (
            ! $terlewat->has($tanggal->toDateString())
            && $tanggal->toDateString() >= $earliestDate
            && $streak < 366
        ) {
            $streak++;
            $tanggal = $tanggal->subDay();
        }

        return $streak;
    }

    /**
     * Kejadian MO hari ini: total slot dan yang sudah dikonfirmasi.
     */
    public static function kejadianMoHariIni(string $pasienId): array
    {
        $hariIni = Carbon::today();
        $base = PengingatKejadian::query()
            ->where('user_pasien_id', $pasienId)
            ->where('jenis', 'mo')
            ->whereDate('waktu_jadwal', $hariIni);

        return [
            'total' => (clone $base)->count(),
            'selesai' => (clone $base)->where('status', PengingatKejadian::STATUS_DIKONFIRMASI)->count(),
        ];
    }

    /**
     * Jadwal CGD hari ini: total peserta dan jumlah log yang sudah diisi.
     */
    public static function cgdHariIni(string $pasienId): array
    {
        $today = Carbon::today()->toDateString();

        $total = JadwalCgdPeserta::query()
            ->whereHas('jadwalCgd', fn ($q) => $q->whereDate('tgl_jadwal_cgd', $today))
            ->whereHas('pasienPmo', fn ($q) => $q->where('id_user', $pasienId))
            ->count();

        $selesai = PengingatCgdLog::query()->forUser($pasienId)
            ->whereDate('tgl_cgd', $today)->count();

        return ['total' => $total, 'selesai' => $selesai];
    }

    /**
     * Tren gula darah: N data terakhir, diurutkan dari terlama ke terbaru.
     */
    public static function trenGdPasien(string $pasienId, int $limit = 14): array
    {
        return PengingatCgdLog::query()->forUser($pasienId)
            ->orderByDesc('tgl_cgd')->orderByDesc('jam_cgd')
            ->limit($limit)->get(['tgl_cgd', 'hasil_mgdl'])
            ->reverse()->values()
            ->map(fn ($r) => ['tgl' => $r->tgl_cgd->format('Y-m-d'), 'hasil' => (int) $r->hasil_mgdl])
            ->all();
    }

    /**
     * Daftar pasien binaan aktif milik seorang PMO.
     */
    public static function pasienBinaan(string $pmoId): Collection
    {
        return PasienPmo::query()->forPmo($pmoId)->active()->get();
    }

    /**
     * Hasil gula darah terakhir (mg/dL) milik pasien, atau null bila belum ada log.
     */
    public static function hasilGdTerakhir(string $pasienId): ?int
    {
        $log = PengingatCgdLog::query()->forUser($pasienId)
            ->orderByDesc('tgl_cgd')->orderByDesc('jam_cgd')->first();

        return $log?->hasil_mgdl;
    }

    /**
     * Distribusi log CGD berdasarkan kategori_hasil.
     */
    public static function distribusiKategoriCgd(): array
    {
        $rows = PengingatCgdLog::query()
            ->selectRaw('kategori_hasil, COUNT(*) as jml')
            ->groupBy('kategori_hasil')->pluck('jml', 'kategori_hasil');

        return [
            'normal' => (int) ($rows['normal'] ?? 0),
            'tidak_terkontrol' => (int) ($rows['tidak_terkontrol'] ?? 0),
            'tinggi' => (int) ($rows['tinggi'] ?? 0),
            'berbahaya' => (int) ($rows['berbahaya'] ?? 0),
        ];
    }

    /**
     * Tren jumlah log CGD per hari dalam 30 hari terakhir (selalu 30 entri, terlama ke terbaru).
     */
    public static function trenCgd30Hari(): array
    {
        $sejak = Carbon::today()->subDays(29)->toDateString();
        $rows = PengingatCgdLog::query()
            ->where('tgl_cgd', '>=', $sejak)
            ->selectRaw('tgl_cgd, COUNT(*) as jml')
            ->groupBy('tgl_cgd')->pluck('jml', 'tgl_cgd');

        $hasil = [];
        for ($i = 29; $i >= 0; $i--) {
            $t = Carbon::today()->subDays($i)->toDateString();
            $hasil[] = ['tgl' => $t, 'jml' => (int) ($rows[$t] ?? 0)];
        }

        return $hasil;
    }

    /**
     * Jumlah kejadian berstatus 'terlewat' yang dijadwalkan hari ini.
     */
    public static function tindakLanjutHariIni(): int
    {
        return PengingatKejadian::query()
            ->whereDate('waktu_jadwal', Carbon::today())
            ->where('status', PengingatKejadian::STATUS_TERLEWAT)->count();
    }
}
