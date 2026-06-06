<?php

namespace App\Services;

use App\Models\Edukasi;
use App\Repos\EdukasiRepository;
use App\Services\Concerns\HasStandardizedMethods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EdukasiService
{
    use HasStandardizedMethods;

    protected static function getEntityName(): string
    {
        return 'Edukasi';
    }

    protected static function getEntityPluralName(): string
    {
        return 'Edukasi';
    }

    public static function getAllEdukasi(array $params): array
    {
        $data = EdukasiRepository::getAllEdukasi(
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

    public static function findEdukasiById(string $id): ?Edukasi
    {
        if (empty($id) || in_array($id, ['create', 'edit'])) {
            return null;
        }

        return EdukasiRepository::findEdukasiById($id);
    }

    public static function createEdukasi(array $data, ?Request $request = null): Edukasi
    {
        $data['slug'] = self::generateSlug($data['judul']);
        $data['created_by'] = Auth::id();
        $data['published_at'] = ($data['is_published'] ?? false) ? now() : null;

        if ($request && $request->hasFile('gambar')) {
            $data['gambar_path'] = $request->file('gambar')->store('edukasi', 'public');
        }

        return EdukasiRepository::createEdukasi($data);
    }

    public static function updateEdukasi(string $id, array $data, ?Request $request = null): bool
    {
        $item = EdukasiRepository::findEdukasiById($id);
        if (! $item) {
            return false;
        }

        $data['updated_by'] = Auth::id();

        if (($data['is_published'] ?? false) && ! $item->published_at) {
            $data['published_at'] = now();
        }

        if ($request && $request->hasFile('gambar')) {
            if ($item->gambar_path && Storage::disk('public')->exists($item->gambar_path)) {
                Storage::disk('public')->delete($item->gambar_path);
            }
            $data['gambar_path'] = $request->file('gambar')->store('edukasi', 'public');
        }

        return EdukasiRepository::updateEdukasi($id, $data);
    }

    public static function deleteEdukasi(string $id): bool
    {
        $item = EdukasiRepository::findEdukasiById($id);
        if (! $item) {
            return false;
        }

        if ($item->gambar_path && Storage::disk('public')->exists($item->gambar_path)) {
            Storage::disk('public')->delete($item->gambar_path);
        }

        return EdukasiRepository::deleteEdukasi($id);
    }

    public static function getStats(): array
    {
        return [
            'total' => Edukasi::count(),
            'published' => Edukasi::where('is_published', true)->count(),
            'draft' => Edukasi::where('is_published', false)->count(),
        ];
    }

    private static function generateSlug(string $judul, ?string $exceptId = null): string
    {
        $base = Str::slug($judul) ?: 'edukasi';
        $slug = $base;
        $i = 1;
        while (EdukasiRepository::slugExists($slug, $exceptId)) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
