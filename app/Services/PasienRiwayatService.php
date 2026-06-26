<?php

namespace App\Services;

use App\Models\JadwalCgdPeserta;
use App\Models\PengingatCgdLog;
use App\Models\PengingatKejadian;
use App\Models\PengingatMoLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PasienRiwayatService
{
    public static function jadwalCgdPasien(string $userId): array
    {
        $peserta = JadwalCgdPeserta::query()
            ->whereHas('pasienPmo', fn ($q) => $q->where('id_user', $userId))
            ->with('jadwalCgd')
            ->get()
            ->filter(fn ($p) => $p->jadwalCgd !== null);

        $today = Carbon::today()->toDateString();

        $map = fn ($p) => [
            'tanggal' => $p->jadwalCgd->tgl_jadwal_cgd,
            'jam' => substr((string) $p->jadwalCgd->jam_mulai, 0, 5),
            'tempat' => $p->jadwalCgd->tempat,
            'puasa' => $p->jadwalCgd->puasa,
            'status' => $p->jadwalCgd->status,
        ];

        $mendatang = $peserta
            ->filter(fn ($p) => $p->jadwalCgd->tgl_jadwal_cgd->toDateString() >= $today)
            ->sortBy(fn ($p) => $p->jadwalCgd->tgl_jadwal_cgd->toDateString())
            ->map($map)->values()->all();

        $lewat = $peserta
            ->filter(fn ($p) => $p->jadwalCgd->tgl_jadwal_cgd->toDateString() < $today)
            ->sortByDesc(fn ($p) => $p->jadwalCgd->tgl_jadwal_cgd->toDateString())
            ->map($map)->values()->all();

        return ['mendatang' => $mendatang, 'lewat' => $lewat];
    }

    public static function riwayatMo(string $userId, array $filter = []): LengthAwarePaginator
    {
        return PengingatMoLog::query()->forUser($userId)
            ->when($filter['dari'] ?? null, fn ($q, $d) => $q->where('tgl_minum_obat', '>=', $d))
            ->when($filter['sampai'] ?? null, fn ($q, $d) => $q->where('tgl_minum_obat', '<=', $d))
            ->orderByDesc('tgl_minum_obat')->orderByDesc('jam_minum_obat')
            ->paginate(15)->withQueryString();
    }

    public static function riwayatCgd(string $userId, array $filter = []): LengthAwarePaginator
    {
        return PengingatCgdLog::query()->forUser($userId)
            ->when($filter['dari'] ?? null, fn ($q, $d) => $q->where('tgl_cgd', '>=', $d))
            ->when($filter['sampai'] ?? null, fn ($q, $d) => $q->where('tgl_cgd', '<=', $d))
            ->orderByDesc('tgl_cgd')->orderByDesc('jam_cgd')
            ->paginate(15)->withQueryString();
    }

    public static function pendingKonfirmasi(string $userId): Collection
    {
        return PengingatKejadian::query()
            ->where('user_pasien_id', $userId)
            ->where('jenis', 'mo')
            ->menunggu()
            ->orderBy('waktu_jadwal')
            ->get();
    }
}
