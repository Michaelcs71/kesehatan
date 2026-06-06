<?php

namespace App\Services;

use App\Models\Pengumuman;
use App\Repos\PengumumanRepository;
use App\Services\Concerns\HasStandardizedMethods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PengumumanService
{
    use HasStandardizedMethods;

    protected static function getEntityName(): string
    {
        return 'Pengumuman';
    }

    protected static function getEntityPluralName(): string
    {
        return 'Pengumuman';
    }

    public static function getAllPengumuman(array $params): array
    {
        $data = PengumumanRepository::getAllPengumuman(
            search: $params['search'] ?? '',
            isPublished: $params['is_published'] ?? null,
            page: ($params['pagenum'] ?? 0) + 1,
            perPage: $params['pagesize'] ?? 10,
        );

        return [
            'TotalRows' => $data->total(),
            'Rows' => $data->items(),
        ];
    }

    public static function findPengumumanById(string $id): ?Pengumuman
    {
        if (empty($id) || in_array($id, ['create', 'edit'])) {
            return null;
        }

        return PengumumanRepository::findPengumumanById($id);
    }

    public static function createPengumuman(array $data, ?Request $request = null): Pengumuman
    {
        $data['slug'] = self::generateSlug($data['judul']);
        $data['created_by'] = Auth::id();
        $data['published_at'] = ($data['is_published'] ?? false) ? now() : null;

        if ($request && $request->hasFile('gambar')) {
            $data['gambar_path'] = $request->file('gambar')->store('pengumuman', 'public');
        }

        return PengumumanRepository::createPengumuman($data);
    }

    public static function updatePengumuman(string $id, array $data, ?Request $request = null): bool
    {
        $item = PengumumanRepository::findPengumumanById($id);
        if (! $item) {
            return false;
        }

        $data['updated_by'] = Auth::id();

        // Set published_at saat pertama kali dipublish
        if (($data['is_published'] ?? false) && ! $item->published_at) {
            $data['published_at'] = now();
        }

        if ($request && $request->hasFile('gambar')) {
            if ($item->gambar_path && Storage::disk('public')->exists($item->gambar_path)) {
                Storage::disk('public')->delete($item->gambar_path);
            }
            $data['gambar_path'] = $request->file('gambar')->store('pengumuman', 'public');
        }

        return PengumumanRepository::updatePengumuman($id, $data);
    }

    public static function deletePengumuman(string $id): bool
    {
        $item = PengumumanRepository::findPengumumanById($id);
        if (! $item) {
            return false;
        }

        if ($item->gambar_path && Storage::disk('public')->exists($item->gambar_path)) {
            Storage::disk('public')->delete($item->gambar_path);
        }

        return PengumumanRepository::deletePengumuman($id);
    }

    public static function getStats(): array
    {
        return [
            'total' => Pengumuman::count(),
            'published' => Pengumuman::where('is_published', true)->count(),
            'draft' => Pengumuman::where('is_published', false)->count(),
        ];
    }

    private static function generateSlug(string $judul, ?string $exceptId = null): string
    {
        $base = Str::slug($judul) ?: 'pengumuman';
        $slug = $base;
        $i = 1;
        while (PengumumanRepository::slugExists($slug, $exceptId)) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
