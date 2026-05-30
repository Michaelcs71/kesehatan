<?php

namespace App\Services;

use App\Models\MasterSatuanObat;
use App\Repos\MasterSatuanObatRepository;
use App\Services\Concerns\HasStandardizedMethods;
use Illuminate\Support\Facades\Auth;

class MasterSatuanObatService
{
    use HasStandardizedMethods;

    protected static function getEntityName(): string
    {
        return 'MasterSatuanObat';
    }

    protected static function getEntityPluralName(): string
    {
        return 'MasterSatuanObats';
    }

    public static function getAllMasterSatuanObats(array $params): array
    {
        $data = MasterSatuanObatRepository::getAllMasterSatuanObats(
            search: $params['search'] ?? '',
            isActive: $params['is_active'] ?? null,
            page: ($params['pagenum'] ?? 0) + 1,
            perPage: $params['pagesize'] ?? 10,
        );

        return [
            'TotalRows' => $data->total(),
            'Rows'      => $data->items(),
        ];
    }

    public static function findMasterSatuanObatById(string $id): ?MasterSatuanObat
    {
        if (empty($id) || in_array($id, ['create', 'edit'])) {
            return null;
        }
        return MasterSatuanObatRepository::findMasterSatuanObatById($id);
    }

    public static function createMasterSatuanObat(array $data): MasterSatuanObat
    {
        $data['created_by'] = Auth::id();
        return MasterSatuanObatRepository::createMasterSatuanObat($data);
    }

    public static function updateMasterSatuanObat(string $id, array $data): bool
    {
        $data['updated_by'] = Auth::id();
        return MasterSatuanObatRepository::updateMasterSatuanObat($id, $data);
    }

    public static function deleteMasterSatuanObat(string $id): bool
    {
        $satuan = MasterSatuanObatRepository::findMasterSatuanObatById($id);

        if (!$satuan) {
            return false;
        }

        if ($satuan->obats_count > 0) {
            throw new \Exception("Satuan ini sedang digunakan oleh {$satuan->obats_count} obat. Hapus atau pindahkan obat tersebut terlebih dahulu.");
        }

        return MasterSatuanObatRepository::deleteMasterSatuanObat($id);
    }

    public static function getActiveOptions(): array
    {
        return MasterSatuanObatRepository::getActiveOptions();
    }

    public static function getStats(): array
    {
        return [
            'total'    => MasterSatuanObat::count(),
            'active'   => MasterSatuanObat::where('is_active', true)->count(),
            'inactive' => MasterSatuanObat::where('is_active', false)->count(),
        ];
    }
}
