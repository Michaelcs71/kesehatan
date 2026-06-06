<?php

namespace App\Repos;

use App\Enums\StatusObat;
use App\Models\MasterObat;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MasterObatRepository
{
    /**
     * Get all obats with pagination + filters
     */
    public static function getAllObats(
        string $search = '',
        string $status = '',
        string $kategoriId = '',
        int $page = 1,
        int $perPage = 10,
        ?User $user = null
    ): LengthAwarePaginator {

        $query = MasterObat::with([
            'kategori:id,nama',
            'satuan:id,nama,singkatan',     // <-- TAMBAH
            'creator:id,name,role',
            'verifier:id,name,role',
        ]);

        // Visibility per role
        if ($user && ! $user->isAdmin()) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('status', StatusObat::APPROVED->value)
                    ->orWhere('created_by', $user->id);
            });
        }

        // Filter status
        if ($status && in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        // Filter kategori (by UUID)
        $query->kategoriId($kategoriId);

        // Search
        $query->search($search);

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    /**
     * Find obat by ID with relations
     */
    public static function findObatById(string $id): ?MasterObat
    {
        return MasterObat::with([
            'kategori:id,nama,deskripsi',
            'satuan:id,nama,singkatan,deskripsi',     // <-- TAMBAH
            'creator:id,name,role,email',
            'verifier:id,name,role',
        ])->find($id);
    }

    /**
     * Create new obat
     */
    public static function createObat(array $data): MasterObat
    {
        return DB::transaction(function () use ($data) {
            return MasterObat::create($data);
        });
    }

    /**
     * Update obat
     */
    public static function updateObat(string $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $obat = MasterObat::find($id);
            if (! $obat) {
                return false;
            }

            return $obat->update($data);
        });
    }

    /**
     * Soft delete obat
     */
    public static function deleteObat(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $obat = MasterObat::find($id);
            if (! $obat) {
                return false;
            }

            return (bool) $obat->delete();
        });
    }
}
