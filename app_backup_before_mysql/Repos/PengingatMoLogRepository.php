<?php

namespace App\Repos;

use App\Models\JadwalMinumObat;
use App\Models\PengingatMoLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PengingatMoLogRepository
{
    /**
     * Get all logs dengan pagination + filter per role
     */
    public static function getAllLogs(
        string $search = '',
        ?string $status = null,
        ?string $jadwalId = null,
        ?string $tanggalStart = null,
        ?string $tanggalEnd = null,
        ?string $patuhKategori = null,
        ?string $forPmoUserId = null,
        ?string $forPasienUserId = null,
        int $page = 1,
        int $perPage = 10
    ): LengthAwarePaginator {

        $query = PengingatMoLog::query()
            ->with([
                'jadwalMo:id,id_pasien_pmo,obat_id,jam_mulai,frekuensi_per_hari',
                'jadwalMo.pasienPmo:id,id_user,pmo_user_id,nama_pasien,nama_pmo',
                'jadwalMo.obat:id,nama,dosis_default,satuan_id',
                'jadwalMo.obat.satuan:id,nama,singkatan',
                'user:id,name',
                'creator:id,name',
            ]);

        $query->search($search);

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($jadwalId)) {
            $query->where('id_jo', $jadwalId);
        }

        if (!empty($tanggalStart) && !empty($tanggalEnd)) {
            $query->whereBetween('tgl_minum_obat', [$tanggalStart, $tanggalEnd]);
        } elseif (!empty($tanggalStart)) {
            $query->where('tgl_minum_obat', '>=', $tanggalStart);
        } elseif (!empty($tanggalEnd)) {
            $query->where('tgl_minum_obat', '<=', $tanggalEnd);
        }

        // Filter by patuh kategori
        if ($patuhKategori === 'tepat_waktu') {
            $query->whereRaw('ABS(patuh_menit) <= 15');
        } elseif ($patuhKategori === 'terlambat') {
            $query->whereRaw('ABS(patuh_menit) BETWEEN 16 AND 60');
        } elseif ($patuhKategori === 'sangat_terlambat') {
            $query->whereRaw('ABS(patuh_menit) > 60');
        }

        // Role filter: PMO cuma lihat log pasien-nya
        if (!empty($forPmoUserId)) {
            $query->whereHas('jadwalMo.pasienPmo', function ($q) use ($forPmoUserId) {
                $q->where('pmo_user_id', $forPmoUserId);
            });
        }

        // Role filter: Pasien cuma lihat log sendiri
        if (!empty($forPasienUserId)) {
            $query->whereHas('jadwalMo.pasienPmo', function ($q) use ($forPasienUserId) {
                $q->where('id_user', $forPasienUserId);
            });
        }

        return $query
            ->orderBy('tgl_minum_obat', 'desc')
            ->orderBy('jam_minum_obat', 'desc')
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    /**
     * Find by ID dengan eager load
     */
    public static function findLogById(string $id): ?PengingatMoLog
    {
        return PengingatMoLog::with([
            'jadwalMo:id,id_pasien_pmo,obat_id,jam_mulai,frekuensi_per_hari,catatan_dosis',
            'jadwalMo.pasienPmo:id,id_user,pmo_user_id,nama_pasien,nama_pmo',
            'jadwalMo.obat:id,nama,dosis_default,satuan_id,aturan_minum',
            'jadwalMo.obat.satuan:id,nama,singkatan',
            'user:id,name',
            'creator:id,name',
            'updater:id,name',
        ])->find($id);
    }

    /**
     * Get jadwal aktif untuk dropdown (filter per role)
     */
    public static function getJadwalOptions(?string $pmoUserId = null, ?string $pasienUserId = null): array
    {
        $query = JadwalMinumObat::query()
            ->where('status', 'aktif')
            ->with([
                'pasienPmo:id,id_user,pmo_user_id,nama_pasien,nama_pmo',
                'obat:id,nama,dosis_default,satuan_id',
                'obat.satuan:id,nama,singkatan',
            ]);

        if ($pmoUserId) {
            $query->whereHas('pasienPmo', fn($q) => $q->where('pmo_user_id', $pmoUserId));
        }

        if ($pasienUserId) {
            $query->whereHas('pasienPmo', fn($q) => $q->where('id_user', $pasienUserId));
        }

        return $query
            ->orderBy('nama_pasien')
            ->get(['id', 'id_pasien_pmo', 'obat_id', 'nama_pasien', 'nama_pmo', 'jam_mulai', 'frekuensi_per_hari'])
            ->map(function ($j) {
                $satuan = $j->obat?->satuan?->singkatan ?? $j->obat?->satuan?->nama;
                $dosis  = $j->obat?->dosis_default ? " {$j->obat->dosis_default}" : '';
                $satuanStr = $satuan ? " ({$satuan})" : '';
                $obatLabel = ($j->obat?->nama ?? 'Obat dihapus') . $dosis . $satuanStr;

                // Generate slot jam dari jam_mulai + frekuensi (for client-side suggest)
                $slots = [];
                if ($j->jam_mulai && $j->frekuensi_per_hari > 0) {
                    $interval = 24 / $j->frekuensi_per_hari;
                    [$h, $m] = array_pad(explode(':', $j->jam_mulai), 2, 0);
                    $startMinutes = ((int) $h) * 60 + ((int) $m);
                    for ($i = 0; $i < $j->frekuensi_per_hari; $i++) {
                        $minutes = ($startMinutes + ($i * $interval * 60)) % (24 * 60);
                        $hh = floor($minutes / 60);
                        $mm = $minutes % 60;
                        $slots[] = sprintf('%02d:%02d', $hh, $mm);
                    }
                }

                return [
                    'id'           => $j->id,
                    'nama_pasien'  => $j->nama_pasien,
                    'nama_pmo'     => $j->nama_pmo,
                    'nama_obat'    => $j->obat?->nama ?? '-',
                    'obat_label'   => $obatLabel,
                    'jam_mulai'    => substr($j->jam_mulai ?? '', 0, 5),
                    'frekuensi'    => $j->frekuensi_per_hari,
                    'slot_jam'     => $slots,
                    'label'        => $j->nama_pasien . ' - ' . $obatLabel,
                ];
            })
            ->toArray();
    }

    /**
     * Create log
     */
    public static function createLog(array $data): PengingatMoLog
    {
        return DB::transaction(function () use ($data) {
            return PengingatMoLog::create($data);
        });
    }

    /**
     * Update log
     */
    public static function updateLog(string $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $log = PengingatMoLog::find($id);
            if (!$log) return false;
            return $log->update($data);
        });
    }

    /**
     * Deactivate
     */
    public static function deactivate(string $id, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $log = PengingatMoLog::find($id);
            if (!$log) return false;
            return $log->update(['status' => 'nonaktif', 'updated_by' => $userId]);
        });
    }

    /**
     * Activate
     */
    public static function activate(string $id, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $log = PengingatMoLog::find($id);
            if (!$log) return false;
            return $log->update(['status' => 'aktif', 'updated_by' => $userId]);
        });
    }

    /**
     * Soft delete
     */
    public static function deleteLog(string $id, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $log = PengingatMoLog::find($id);
            if (!$log) return false;

            $log->deleted_by = $userId;
            $log->save();
            return (bool) $log->delete();
        });
    }
}
