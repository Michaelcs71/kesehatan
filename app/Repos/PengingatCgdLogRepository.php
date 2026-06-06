<?php

namespace App\Repos;

use App\Models\JadwalCgd;
use App\Models\PengingatCgdLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PengingatCgdLogRepository
{
    /**
     * Get all logs dengan pagination + filter
     */
    public static function getAllLogs(
        string $search = '',
        ?string $status = null,
        ?string $kategoriHasil = null,
        ?string $cgdId = null,
        ?string $tanggalStart = null,
        ?string $tanggalEnd = null,
        ?string $forUserId = null,
        ?string $forPmoUserId = null,
        int $page = 1,
        int $perPage = 10
    ): LengthAwarePaginator {

        $query = PengingatCgdLog::query()
            ->with([
                'jadwalCgd:id,tgl_jadwal_cgd,jam_mulai,jam_berakhir,puasa,tempat,status',
                'user:id,name',
                'creator:id,name',
            ]);

        $query->search($search);

        if (! empty($status)) {
            $query->where('status', $status);
        }

        if (! empty($kategoriHasil)) {
            $query->where('kategori_hasil', $kategoriHasil);
        }

        if (! empty($cgdId)) {
            $query->where('id_cgd', $cgdId);
        }

        if (! empty($tanggalStart) && ! empty($tanggalEnd)) {
            $query->whereBetween('tgl_cgd', [$tanggalStart, $tanggalEnd]);
        } elseif (! empty($tanggalStart)) {
            $query->where('tgl_cgd', '>=', $tanggalStart);
        } elseif (! empty($tanggalEnd)) {
            $query->where('tgl_cgd', '<=', $tanggalEnd);
        }

        // Role filter: Pasien cuma lihat log sendiri
        if (! empty($forUserId)) {
            $query->where('id_user', $forUserId);
        }

        // Role filter: PMO cuma lihat log pasien yang dia damping
        if (! empty($forPmoUserId)) {
            $query->whereHas('user.pasienPmoAsPasien', function ($q) use ($forPmoUserId) {
                $q->where('pmo_user_id', $forPmoUserId)
                    ->where('is_active', true);
            });
        }

        return $query
            ->orderBy('tgl_cgd', 'desc')
            ->orderBy('jam_cgd', 'desc')
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    /**
     * Find by ID dengan eager load
     */
    public static function findLogById(string $id): ?PengingatCgdLog
    {
        return PengingatCgdLog::with([
            'jadwalCgd:id,tgl_jadwal_cgd,jam_mulai,jam_berakhir,puasa,tempat,catatan,status',
            'user:id,name',
            'user.biodata:id,user_id,jenis_kelamin,tanggal_lahir',
            'creator:id,name',
            'updater:id,name',
        ])->find($id);
    }

    /**
     * Get jadwal CGD aktif untuk dropdown
     * Filter: status='aktif' dan tgl_jadwal_cgd <= today (hanya event yang sudah/sedang berlangsung)
     */
    public static function getJadwalCgdOptions(): array
    {
        return JadwalCgd::query()
            ->where('status', 'aktif')
            ->where('tgl_jadwal_cgd', '<=', now()->format('Y-m-d'))
            ->orderBy('tgl_jadwal_cgd', 'desc')
            ->get(['id', 'tgl_jadwal_cgd', 'jam_mulai', 'jam_berakhir', 'puasa', 'tempat'])
            ->map(function ($c) {
                $tgl = $c->tgl_jadwal_cgd?->format('d M Y');
                $jam = substr($c->jam_mulai ?? '', 0, 5).' - '.substr($c->jam_berakhir ?? '', 0, 5);

                return [
                    'id' => $c->id,
                    'tgl' => $c->tgl_jadwal_cgd?->format('Y-m-d'),
                    'tgl_display' => $tgl,
                    'jam' => $jam,
                    'puasa' => $c->puasa,
                    'tempat' => $c->tempat,
                    'label' => "{$tgl} | {$jam} | {$c->tempat} (Puasa: {$c->puasa})",
                ];
            })
            ->toArray();
    }

    /**
     * Create log baru
     */
    public static function createLog(array $data): PengingatCgdLog
    {
        return DB::transaction(function () use ($data) {
            return PengingatCgdLog::create($data);
        });
    }

    /**
     * Update log
     */
    public static function updateLog(string $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $log = PengingatCgdLog::find($id);
            if (! $log) {
                return false;
            }

            return $log->update($data);
        });
    }

    /**
     * Deactivate
     */
    public static function deactivate(string $id, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $log = PengingatCgdLog::find($id);
            if (! $log) {
                return false;
            }

            return $log->update(['status' => 'nonaktif', 'updated_by' => $userId]);
        });
    }

    /**
     * Activate
     */
    public static function activate(string $id, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $log = PengingatCgdLog::find($id);
            if (! $log) {
                return false;
            }

            return $log->update(['status' => 'aktif', 'updated_by' => $userId]);
        });
    }

    /**
     * Soft delete
     */
    public static function deleteLog(string $id, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $log = PengingatCgdLog::find($id);
            if (! $log) {
                return false;
            }

            $log->deleted_by = $userId;
            $log->save();

            return (bool) $log->delete();
        });
    }
}
