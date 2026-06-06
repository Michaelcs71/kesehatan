<?php

namespace App\Repos;

use App\Models\JadwalCgd;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class JadwalCgdRepository
{
    /**
     * Get all jadwal CGD dengan pagination + filter
     */
    public static function getAllJadwal(
        string $search = '',
        ?string $status = null,
        ?string $timeFilter = null,   // 'upcoming' | 'past' | 'today'
        int $page = 1,
        int $perPage = 10
    ): LengthAwarePaginator {

        $query = JadwalCgd::query()->with('creator:id,name');

        $query->search($search);

        if (! empty($status)) {
            $query->where('status', $status);
        }

        if ($timeFilter === 'upcoming') {
            $query->where('tgl_jadwal_cgd', '>=', now()->format('Y-m-d'));
        } elseif ($timeFilter === 'past') {
            $query->where('tgl_jadwal_cgd', '<', now()->format('Y-m-d'));
        } elseif ($timeFilter === 'today') {
            $query->where('tgl_jadwal_cgd', now()->format('Y-m-d'));
        }

        return $query
            ->orderBy('tgl_jadwal_cgd', 'desc')
            ->orderBy('jam_mulai', 'desc')
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    /**
     * Find by ID dengan eager load
     */
    public static function findJadwalById(string $id): ?JadwalCgd
    {
        return JadwalCgd::with([
            'creator:id,name',
            'updater:id,name',
        ])->find($id);
    }

    /**
     * Create jadwal baru
     */
    public static function createJadwal(array $data): JadwalCgd
    {
        return DB::transaction(function () use ($data) {
            return JadwalCgd::create($data);
        });
    }

    /**
     * Update jadwal
     */
    public static function updateJadwal(string $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $jadwal = JadwalCgd::find($id);
            if (! $jadwal) {
                return false;
            }

            return $jadwal->update($data);
        });
    }

    /**
     * Status: deactivate
     */
    public static function deactivate(string $id, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $jadwal = JadwalCgd::find($id);
            if (! $jadwal) {
                return false;
            }

            return $jadwal->update([
                'status' => 'nonaktif',
                'updated_by' => $userId,
            ]);
        });
    }

    /**
     * Status: activate
     */
    public static function activate(string $id, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $jadwal = JadwalCgd::find($id);
            if (! $jadwal) {
                return false;
            }

            return $jadwal->update([
                'status' => 'aktif',
                'updated_by' => $userId,
            ]);
        });
    }

    /**
     * Status: mark selesai
     */
    public static function markSelesai(string $id, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $jadwal = JadwalCgd::find($id);
            if (! $jadwal) {
                return false;
            }

            return $jadwal->update([
                'status' => 'selesai',
                'updated_by' => $userId,
            ]);
        });
    }

    /**
     * Soft delete
     */
    public static function deleteJadwal(string $id, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $jadwal = JadwalCgd::find($id);
            if (! $jadwal) {
                return false;
            }

            $jadwal->deleted_by = $userId;
            $jadwal->save();

            return (bool) $jadwal->delete();
        });
    }

    /**
     * Get upcoming CGD untuk widget dashboard (jadwal mendatang yang aktif)
     */
    public static function getUpcoming(int $limit = 5): array
    {
        return JadwalCgd::query()
            ->upcoming()
            ->with('creator:id,name')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Auto-mark CGD lewat sebagai 'selesai' (untuk scheduled job nanti)
     * Return jumlah yang di-update
     */
    public static function autoMarkSelesaiPastEvents(): int
    {
        return JadwalCgd::query()
            ->where('status', 'aktif')
            ->where('tgl_jadwal_cgd', '<', now()->format('Y-m-d'))
            ->update([
                'status' => 'selesai',
                'updated_at' => now(),
            ]);
    }
}
