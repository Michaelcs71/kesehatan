<?php

namespace App\Repos;

use App\Models\MasterSatuanObat;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MasterSatuanObatRepository
{
    public static function getAllMasterSatuanObats(
        string $search = '',
        ?string $isActive = null,
        int $page = 1,
        int $perPage = 10
    ): LengthAwarePaginator {

        $query = MasterSatuanObat::with(['creator:id,name'])
            ->withCount('obats');

        $query->search($search);

        if ($isActive !== null && $isActive !== '') {
            $query->where('is_active', $isActive === '1' || $isActive === 'true');
        }

        return $query
            ->orderBy('nama', 'asc')
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    public static function getActiveOptions(): array
    {
        return MasterSatuanObat::active()
            ->orderBy('nama')
            ->get(['id', 'nama', 'singkatan'])
            ->toArray();
    }

    public static function findMasterSatuanObatById(string $id): ?MasterSatuanObat
    {
        return MasterSatuanObat::with(['creator:id,name', 'updater:id,name'])
            ->withCount('obats')
            ->find($id);
    }

    public static function createMasterSatuanObat(array $data): MasterSatuanObat
    {
        return DB::transaction(fn () => MasterSatuanObat::create($data));
    }

    public static function updateMasterSatuanObat(string $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $item = MasterSatuanObat::find($id);

            return $item ? $item->update($data) : false;
        });
    }

    public static function deleteMasterSatuanObat(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $item = MasterSatuanObat::find($id);

            return $item ? (bool) $item->delete() : false;
        });
    }
}
