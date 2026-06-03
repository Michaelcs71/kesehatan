<?php

namespace App\Repos;

use App\Models\PengingatKejadian;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PengingatKejadianRepository
{
    public static function firstOrCreateUntukSlot(string $jenis, string $jadwalId, Carbon $waktuJadwal, array $atribut): PengingatKejadian
    {
        return PengingatKejadian::firstOrCreate(
            ['jenis' => $jenis, 'jadwal_id' => $jadwalId, 'waktu_jadwal' => $waktuJadwal],
            array_merge($atribut, ['status' => PengingatKejadian::STATUS_MENUNGGU]),
        );
    }

    public static function menunggu(): Collection
    {
        return PengingatKejadian::menunggu()->orderBy('waktu_jadwal')->get();
    }

    public static function tandaiDikirim(PengingatKejadian $k, string $kanal, string $target, Carbon $waktu): void
    {
        DB::transaction(function () use ($k, $kanal, $target, $waktu) {
            if ($kanal === 'push') {
                $k->increment('jumlah_push');
            } elseif ($target === 'pmo') {
                $k->increment('jumlah_wa_pmo');
            } else {
                $k->increment('jumlah_wa_pasien');
            }

            $update = ['terakhir_dikirim_pada' => $waktu];
            if ($target === 'pmo') {
                $update['eskalasi_pmo'] = true;
            }
            $k->forceFill($update)->save();
        });
    }

    public static function tandaiTerlewat(PengingatKejadian $k): void
    {
        $k->forceFill(['status' => PengingatKejadian::STATUS_TERLEWAT])->save();
    }

    public static function tandaiDikonfirmasi(PengingatKejadian $k, string $logId, Carbon $waktu): void
    {
        $k->forceFill([
            'status'            => PengingatKejadian::STATUS_DIKONFIRMASI,
            'konfirmasi_log_id' => $logId,
            'dikonfirmasi_pada' => $waktu,
        ])->save();
    }
}
