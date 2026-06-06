<?php

namespace App\Repos;

use App\Models\MasterKategoriObat;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MasterKategoriObatRepository
{
    /**
     * Get all kategori obat with pagination
     */
    public static function getAllMasterKategoriObats(
        string $search = '',
        ?string $isActive = null,
        int $page = 1,
        int $perPage = 10
    ): LengthAwarePaginator {

        $query = MasterKategoriObat::with(['creator:id,name'])
            ->withCount('obats');

        $query->search($search);

        if ($isActive !== null && $isActive !== '') {
            $query->where('is_active', $isActive === '1' || $isActive === 'true');
        }

        return $query
            ->orderBy('nama', 'asc')
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    /**
     * Get all active kategori (untuk dropdown)
     */
    public static function getActiveOptions(): array
    {
        return MasterKategoriObat::active()
            ->orderBy('nama')
            ->get(['id', 'nama'])
            ->toArray();
    }

    public static function findMasterKategoriObatById(string $id): ?MasterKategoriObat
    {
        return MasterKategoriObat::with(['creator:id,name', 'updater:id,name'])
            ->withCount('obats')
            ->find($id);
    }

    public static function createMasterKategoriObat(array $data): MasterKategoriObat
    {
        return DB::transaction(fn () => MasterKategoriObat::create($data));
    }

    public static function updateMasterKategoriObat(string $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $item = MasterKategoriObat::find($id);

            return $item ? $item->update($data) : false;
        });
    }

    public static function deleteMasterKategoriObat(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $item = MasterKategoriObat::find($id);

            return $item ? (bool) $item->delete() : false;
        });
    }
}
