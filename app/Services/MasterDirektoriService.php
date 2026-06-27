<?php

namespace App\Services;

use App\Models\JadwalMinumObat;
use App\Models\PasienPmo;
use App\Models\PengingatCgdLog;
use App\Models\PengingatMoLog;
use App\Repos\DashboardRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MasterDirektoriService
{
    public static function daftarPasien(array $filter = []): LengthAwarePaginator
    {
        return PasienPmo::query()->active()
            ->when($filter['cari'] ?? null, fn ($q, $c) => $q->search($c))
            ->with(['pasien', 'pmo'])
            ->orderBy('nama_pasien')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($pp) => [
                'id_user' => $pp->id_user,
                'nama' => $pp->nama_pasien,
                'nik' => $pp->nik,
                'status_diabetes' => $pp->status_diabetes,
                'nama_pmo' => $pp->nama_pmo,
                'kepatuhan' => DashboardRepository::hitungKepatuhanMo($pp->id_user),
                'is_active' => $pp->is_active,
            ]);
    }

    public static function detailPasien(string $userId): ?array
    {
        $pp = PasienPmo::query()->where('id_user', $userId)
            ->with(['pasien.biodata', 'pmo'])->first();

        if (! $pp) {
            return null;
        }

        $pasien = $pp->pasien;

        return [
            'nama' => $pp->nama_pasien,
            'nik' => $pp->nik,
            'jenis_kelamin' => $pasien?->biodata?->jenis_kelamin_label ?? '-',
            'tanggal_lahir' => $pasien?->biodata?->tanggal_lahir,
            'alamat' => $pasien?->biodata?->alamat_lengkap ?? '-',
            'kontak' => $pasien?->whatsapp_number ?? '-',
            'status_diabetes' => $pp->status_diabetes,
            'pmo' => $pp->pmo
                ? ['nama' => $pp->nama_pmo, 'kontak' => $pp->pmo->whatsapp_number ?? '-']
                : null,
            'kepatuhan' => DashboardRepository::hitungKepatuhanMo($userId),
            'jumlah_cgd' => PengingatCgdLog::query()->forUser($userId)->count(),
            'jadwal_mo' => JadwalMinumObat::query()->active()
                ->whereHas('pasienPmo', fn ($q) => $q->where('id_user', $userId))
                ->with('obat')->get()
                ->map(fn ($j) => [
                    'obat' => $j->obat?->nama ?? '-',
                    'jam' => substr((string) $j->jam_mulai, 0, 5),
                    'frekuensi' => $j->frekuensi_per_hari,
                ])->all(),
            'jadwal_cgd' => PasienRiwayatService::jadwalCgdPasien($userId)['mendatang'],
            'riwayat_mo' => PengingatMoLog::query()->forUser($userId)
                ->orderByDesc('tgl_minum_obat')->orderByDesc('jam_minum_obat')->limit(5)->get(),
            'riwayat_cgd' => PengingatCgdLog::query()->forUser($userId)
                ->orderByDesc('tgl_cgd')->orderByDesc('jam_cgd')->limit(5)->get(),
        ];
    }
}
