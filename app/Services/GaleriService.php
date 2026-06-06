<?php

namespace App\Services;

use App\Models\Galeri;
use App\Repos\GaleriRepository;
use App\Services\Concerns\HasStandardizedMethods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GaleriService
{
    use HasStandardizedMethods;

    protected static function getEntityName(): string
    {
        return 'Galeri';
    }

    protected static function getEntityPluralName(): string
    {
        return 'Galeri';
    }

    public static function getAllGaleri(array $params): array
    {
        $data = GaleriRepository::getAllGaleri(
            search: $params['search'] ?? '',
            isPublished: $params['is_published'] ?? null,
            page: ($params['pagenum'] ?? 0) + 1,
            perPage: $params['pagesize'] ?? 12,
        );

        return [
            'TotalRows' => $data->total(),
            'Rows' => $data->items(),
        ];
    }

    public static function findGaleriById(string $id): ?Galeri
    {
        if (empty($id) || in_array($id, ['create', 'edit'])) {
            return null;
        }

        return GaleriRepository::findGaleriById($id);
    }

    public static function createGaleri(array $data, ?Request $request = null): Galeri
    {
        $data['created_by'] = Auth::id();

        if ($request && $request->hasFile('gambar')) {
            $data['gambar_path'] = $request->file('gambar')->store('galeri', 'public');
        }

        return GaleriRepository::createGaleri($data);
    }

    public static function updateGaleri(string $id, array $data, ?Request $request = null): bool
    {
        $item = GaleriRepository::findGaleriById($id);
        if (! $item) {
            return false;
        }

        $data['updated_by'] = Auth::id();

        if ($request && $request->hasFile('gambar')) {
            if ($item->gambar_path && Storage::disk('public')->exists($item->gambar_path)) {
                Storage::disk('public')->delete($item->gambar_path);
            }
            $data['gambar_path'] = $request->file('gambar')->store('galeri', 'public');
        }

        return GaleriRepository::updateGaleri($id, $data);
    }

    public static function deleteGaleri(string $id): bool
    {
        $item = GaleriRepository::findGaleriById($id);
        if (! $item) {
            return false;
        }

        if ($item->gambar_path && Storage::disk('public')->exists($item->gambar_path)) {
            Storage::disk('public')->delete($item->gambar_path);
        }

        return GaleriRepository::deleteGaleri($id);
    }

    public static function getStats(): array
    {
        return [
            'total' => Galeri::count(),
            'published' => Galeri::where('is_published', true)->count(),
            'draft' => Galeri::where('is_published', false)->count(),
        ];
    }
}
