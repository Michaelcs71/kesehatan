<?php

namespace App\Services;

use App\Models\PengingatCgdLog;
use App\Models\PengingatMoLog;
use Illuminate\Support\Carbon;

/**
 * Laporan Kepatuhan Pasien.
 * Mengagregasi log konfirmasi Minum Obat (MO) dan Cek Gula Darah (CGD)
 * menjadi ringkasan kepatuhan per pasien dalam rentang tanggal tertentu.
 */
class LaporanKepatuhanService
{
    // Ambang toleransi kepatuhan minum obat (menit) — selaras dengan accessor patuh_kategori
    private const TOLERANSI_TEPAT = 15;

    private const TOLERANSI_TELAT = 60;

    public static function getReport(?string $start = null, ?string $end = null): array
    {
        $end = $end ? Carbon::parse($end)->endOfDay() : Carbon::now()->endOfDay();
        $start = $start ? Carbon::parse($start)->startOfDay() : (clone $end)->subDays(29)->startOfDay();

        $mo = self::buildMoReport($start, $end);
        $cgd = self::buildCgdReport($start, $end);

        return [
            'periode' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'mo' => $mo,
            'cgd' => $cgd,
        ];
    }

    /* ===================== MINUM OBAT ===================== */

    private static function buildMoReport(Carbon $start, Carbon $end): array
    {
        $logs = PengingatMoLog::active()
            ->betweenDates($start->toDateString(), $end->toDateString())
            ->get(['id_user', 'nama_pasien', 'patuh_menit']);

        $rows = [];
        $total = ['tepat' => 0, 'telat' => 0, 'sangat' => 0, 'total' => 0];

        foreach ($logs->groupBy(fn ($l) => $l->id_user ?: ('nama:'.$l->nama_pasien)) as $group) {
            $nama = $group->first()->nama_pasien ?: 'Tanpa Nama';
            $tepat = 0;
            $telat = 0;
            $sangat = 0;

            foreach ($group as $log) {
                $abs = abs((int) $log->patuh_menit);
                if ($abs <= self::TOLERANSI_TEPAT) {
                    $tepat++;
                } elseif ($abs <= self::TOLERANSI_TELAT) {
                    $telat++;
                } else {
                    $sangat++;
                }
            }

            $jumlah = $group->count();
            $persen = $jumlah > 0 ? round($tepat / $jumlah * 100, 1) : 0;

            $rows[] = [
                'nama' => $nama,
                'total' => $jumlah,
                'tepat' => $tepat,
                'telat' => $telat,
                'sangat' => $sangat,
                'persen' => $persen,
                'level' => self::levelKepatuhan($persen),
            ];

            $total['tepat'] += $tepat;
            $total['telat'] += $telat;
            $total['sangat'] += $sangat;
            $total['total'] += $jumlah;
        }

        // Urutkan: kepatuhan tertinggi dulu, lalu yang paling aktif
        usort($rows, fn ($a, $b) => $b['persen'] <=> $a['persen'] ?: $b['total'] <=> $a['total']);

        $persenTotal = $total['total'] > 0 ? round($total['tepat'] / $total['total'] * 100, 1) : 0;

        return [
            'rows' => $rows,
            'jumlah_pasien' => count($rows),
            'total' => $total['total'],
            'tepat' => $total['tepat'],
            'telat' => $total['telat'],
            'sangat' => $total['sangat'],
            'persen' => $persenTotal,
        ];
    }

    /* ===================== CEK GULA DARAH ===================== */

    private static function buildCgdReport(Carbon $start, Carbon $end): array
    {
        $logs = PengingatCgdLog::active()
            ->betweenDates($start->toDateString(), $end->toDateString())
            ->get(['id_user', 'nama_pasien', 'kategori_hasil', 'hasil_mgdl']);

        $rows = [];
        $dist = ['normal' => 0, 'tidak_terkontrol' => 0, 'tinggi' => 0, 'berbahaya' => 0];

        foreach ($logs->groupBy(fn ($l) => $l->id_user ?: ('nama:'.$l->nama_pasien)) as $group) {
            $nama = $group->first()->nama_pasien ?: 'Tanpa Nama';
            $count = ['normal' => 0, 'tidak_terkontrol' => 0, 'tinggi' => 0, 'berbahaya' => 0];

            foreach ($group as $log) {
                $kat = $log->kategori_hasil;
                if (isset($count[$kat])) {
                    $count[$kat]++;
                    $dist[$kat]++;
                }
            }

            $jumlah = $group->count();
            $terkontrol = $jumlah > 0 ? round($count['normal'] / $jumlah * 100, 1) : 0;

            $rows[] = [
                'nama' => $nama,
                'total' => $jumlah,
                'normal' => $count['normal'],
                'tidak_terkontrol' => $count['tidak_terkontrol'],
                'tinggi' => $count['tinggi'],
                'berbahaya' => $count['berbahaya'],
                'rata_mgdl' => round($group->avg('hasil_mgdl')),
                'persen' => $terkontrol,
                'level' => self::levelKepatuhan($terkontrol),
            ];
        }

        usort($rows, fn ($a, $b) => $b['persen'] <=> $a['persen'] ?: $b['total'] <=> $a['total']);

        $totalCek = array_sum($dist);
        $persenTerkontrol = $totalCek > 0 ? round($dist['normal'] / $totalCek * 100, 1) : 0;

        return [
            'rows' => $rows,
            'jumlah_pasien' => count($rows),
            'total' => $totalCek,
            'distribusi' => $dist,
            'persen' => $persenTerkontrol,
        ];
    }

    private static function levelKepatuhan(float $persen): string
    {
        return match (true) {
            $persen >= 80 => 'baik',
            $persen >= 50 => 'cukup',
            default => 'kurang',
        };
    }
}
