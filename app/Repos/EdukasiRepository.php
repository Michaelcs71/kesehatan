<?php

namespace App\Repos;

use App\Models\Edukasi;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EdukasiRepository
{
    public static function getAllEdukasi(
        string $search = '',
        ?string $isPublished = null,
        int $page = 1,
        int $perPage = 10
    ): LengthAwarePaginator {

        $query = Edukasi::with(['creator:id,name']);

        $query->search($search);

        if ($isPublished !== null && $isPublished !== '') {
            $query->where('is_published', $isPublished === '1' || $isPublished === 'true');
        }

        return $query
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    public static function findEdukasiById(string $id): ?Edukasi
    {
        return Edukasi::with(['creator:id,name', 'updater:id,name'])->find($id);
    }

    public static function createEdukasi(array $data): Edukasi
    {
        return DB::transaction(fn () => Edukasi::create($data));
    }

    public static function updateEdukasi(string $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $item = Edukasi::find($id);

            return $item ? $item->update($data) : false;
        });
    }

    public static function deleteEdukasi(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $item = Edukasi::find($id);

            return $item ? (bool) $item->delete() : false;
        });
    }

    public static function slugExists(string $slug, ?string $exceptId = null): bool
    {
        return Edukasi::where('slug', $slug)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }

    /* ===== PUBLIC ===== */

    public static function getPublished(int $limit = 60): Collection
    {
        return Edukasi::published()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public static function findPublishedBySlug(string $slug): ?Edukasi
    {
        return Edukasi::published()->where('slug', $slug)->first();
    }
}
