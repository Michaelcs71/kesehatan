<?php

namespace App\Services;

use App\Models\MasterKategoriObat;
use App\Repos\MasterKategoriObatRepository;
use App\Services\Concerns\HasStandardizedMethods;
use Illuminate\Support\Facades\Auth;

class MasterKategoriObatService
{
    use HasStandardizedMethods;

    protected static function getEntityName(): string
    {
        return 'MasterKategoriObat';
    }

    protected static function getEntityPluralName(): string
    {
        return 'MasterKategoriObats';
    }

    public static function getAllMasterKategoriObats(array $params): array
    {
        $data = MasterKategoriObatRepository::getAllMasterKategoriObats(
            search: $params['search'] ?? '',
            isActive: $params['is_active'] ?? null,
            page: ($params['pagenum'] ?? 0) + 1,
            perPage: $params['pagesize'] ?? 10,
        );

        return [
            'TotalRows' => $data->total(),
            'Rows' => $data->items(),
        ];
    }

    public static function findMasterKategoriObatById(string $id): ?MasterKategoriObat
    {
        if (empty($id) || in_array($id, ['create', 'edit'])) {
            return null;
        }

        return MasterKategoriObatRepository::findMasterKategoriObatById($id);
    }

    public static function createMasterKategoriObat(array $data): MasterKategoriObat
    {
        $data['created_by'] = Auth::id();

        return MasterKategoriObatRepository::createMasterKategoriObat($data);
    }

    public static function updateMasterKategoriObat(string $id, array $data): bool
    {
        $data['updated_by'] = Auth::id();

        return MasterKategoriObatRepository::updateMasterKategoriObat($id, $data);
    }

    public static function deleteMasterKategoriObat(string $id): bool
    {
        $kategori = MasterKategoriObatRepository::findMasterKategoriObatById($id);

        if (! $kategori) {
            return false;
        }

        // Validasi: tidak boleh delete kalau masih dipakai oleh master obat
        if ($kategori->obats_count > 0) {
            throw new \Exception("Kategori ini sedang digunakan oleh {$kategori->obats_count} obat. Hapus atau pindahkan obat tersebut terlebih dahulu.");
        }

        return MasterKategoriObatRepository::deleteMasterKategoriObat($id);
    }

    /**
     * Get options untuk dropdown (active only)
     */
    public static function getActiveOptions(): array
    {
        return MasterKategoriObatRepository::getActiveOptions();
    }

    /**
     * Get stats
     */
    public static function getStats(): array
    {
        return [
            'total' => MasterKategoriObat::count(),
            'active' => MasterKategoriObat::where('is_active', true)->count(),
            'inactive' => MasterKategoriObat::where('is_active', false)->count(),
        ];
    }
}
