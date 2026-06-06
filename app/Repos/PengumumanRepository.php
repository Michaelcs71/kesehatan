<?php

namespace App\Repos;

use App\Models\Pengumuman;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PengumumanRepository
{
    public static function getAllPengumuman(
        string $search = '',
        ?string $isPublished = null,
        int $page = 1,
        int $perPage = 10
    ): LengthAwarePaginator {

        $query = Pengumuman::with(['creator:id,name']);

        $query->search($search);

        if ($isPublished !== null && $isPublished !== '') {
            $query->where('is_published', $isPublished === '1' || $isPublished === 'true');
        }

        return $query
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    public static function findPengumumanById(string $id): ?Pengumuman
    {
        return Pengumuman::with(['creator:id,name', 'updater:id,name'])->find($id);
    }

    public static function createPengumuman(array $data): Pengumuman
    {
        return DB::transaction(fn () => Pengumuman::create($data));
    }

    public static function updatePengumuman(string $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $item = Pengumuman::find($id);

            return $item ? $item->update($data) : false;
        });
    }

    public static function deletePengumuman(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $item = Pengumuman::find($id);

            return $item ? (bool) $item->delete() : false;
        });
    }

    public static function slugExists(string $slug, ?string $exceptId = null): bool
    {
        return Pengumuman::where('slug', $slug)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }

    /* ===== PUBLIC ===== */

    public static function getPublished(int $limit = 50): Collection
    {
        return Pengumuman::published()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
