<?php

namespace App\Repos;

use App\Models\Galeri;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GaleriRepository
{
    public static function getAllGaleri(
        string $search = '',
        ?string $isPublished = null,
        int $page = 1,
        int $perPage = 12
    ): LengthAwarePaginator {

        $query = Galeri::with(['creator:id,name']);

        $query->search($search);

        if ($isPublished !== null && $isPublished !== '') {
            $query->where('is_published', $isPublished === '1' || $isPublished === 'true');
        }

        return $query
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    public static function findGaleriById(string $id): ?Galeri
    {
        return Galeri::with(['creator:id,name', 'updater:id,name'])->find($id);
    }

    public static function createGaleri(array $data): Galeri
    {
        return DB::transaction(fn () => Galeri::create($data));
    }

    public static function updateGaleri(string $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $item = Galeri::find($id);

            return $item ? $item->update($data) : false;
        });
    }

    public static function deleteGaleri(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $item = Galeri::find($id);

            return $item ? (bool) $item->delete() : false;
        });
    }

    /* ===== PUBLIC ===== */

    public static function getPublished(int $limit = 60): Collection
    {
        return Galeri::published()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
