<?php

namespace App\Services;

use App\Models\PasienPmo;
use App\Repos\PasienPmoRepository;
use Illuminate\Support\Facades\Auth;

class PasienPmoService
{
    public static function getAllMappings(array $params): array
    {
        /** @var \App\Models\User $loggedInUser */
        $loggedInUser = Auth::user();

        // Default filter: kalau bukan admin/superadmin, cuma lihat mapping sendiri
        $pmoUserId = $params['pmo_user_id'] ?? null;
        if (!$loggedInUser->isSuperadmin() && !$loggedInUser->isAdmin()) {
            if ($loggedInUser->isPmo()) {
                $pmoUserId = $loggedInUser->id;
            }
        }

        $data = PasienPmoRepository::getAllMappings(
            search: $params['search'] ?? '',
            isActive: $params['is_active'] ?? null,
            jenisPmo: $params['jenis_pmo'] ?? null,
            statusDiabetes: $params['status_diabetes'] ?? null,
            pmoUserId: $pmoUserId,
            page: ($params['pagenum'] ?? 0) + 1,
            perPage: $params['pagesize'] ?? 10,
        );

        return [
            'TotalRows' => $data->total(),
            'Rows'      => $data->items(),
        ];
    }

    public static function findMappingById(string $id): ?PasienPmo
    {
        if (empty($id) || in_array($id, ['create', 'edit'])) {
            return null;
        }
        return PasienPmoRepository::findMappingById($id);
    }

    public static function getPmoOptions(): array
    {
        return PasienPmoRepository::getPmoOptions();
    }

    public static function getPasienOptions(?string $excludeMappingId = null): array
    {
        return PasienPmoRepository::getPasienOptions($excludeMappingId);
    }

    /**
     * Bulk create mappings (1 PMO → multiple pasien, status_diabetes per pasien)
     */
    public static function bulkCreate(array $data): array
    {
        /** @var \App\Models\User $loggedInUser */
        $loggedInUser = Auth::user();

        // Kalau login sebagai PMO, paksa pmo_user_id = id sendiri
        if ($loggedInUser->isPmo()) {
            $data['pmo_user_id'] = $loggedInUser->id;
        }

        $commonData = [
            'jenis_pmo'     => $data['jenis_pmo'],
            'tanggal_regis' => $data['tanggal_regis'],
            'catatan'       => $data['catatan'] ?? null,
            'created_by'    => $loggedInUser->id,
        ];

        $created = PasienPmoRepository::bulkCreate(
            pmoData: ['pmo_user_id' => $data['pmo_user_id']],
            pasienItems: $data['pasiens'],   // ← array of {pasien_id, status_diabetes}
            commonData: $commonData,
        );

        return [
            'count'    => count($created),
            'mappings' => $created,
        ];
    }

    /**
     * Update single mapping
     */
    public static function updateMapping(string $id, array $data): bool
    {
        /** @var \App\Models\User $loggedInUser */
        $loggedInUser = Auth::user();

        $mapping = PasienPmoRepository::findMappingById($id);
        if (!$mapping) {
            throw new \Exception('Mapping tidak ditemukan.');
        }

        // Kalau pasien diubah, cek apakah pasien baru sudah punya mapping aktif lain
        if (!empty($data['id_user']) && $data['id_user'] !== $mapping->id_user) {
            $hasActive = PasienPmo::where('id_user', $data['id_user'])
                ->where('is_active', true)
                ->where('id', '!=', $id)
                ->exists();
            if ($hasActive && ($data['is_active'] ?? $mapping->is_active)) {
                throw new \Exception('Pasien yang dipilih sudah punya mapping aktif lain.');
            }
        }

        $data['updated_by'] = $loggedInUser->id;

        return PasienPmoRepository::updateMapping($id, $data);
    }

    public static function deactivate(string $id): bool
    {
        return PasienPmoRepository::deactivate($id, Auth::id());
    }

    public static function activate(string $id): bool
    {
        return PasienPmoRepository::activate($id, Auth::id());
    }

    public static function deleteMapping(string $id): bool
    {
        return PasienPmoRepository::deleteMapping($id, Auth::id());
    }

    public static function getStats(): array
    {
        return [
            'total'    => PasienPmo::count(),
            'active'   => PasienPmo::where('is_active', true)->count(),
            'inactive' => PasienPmo::where('is_active', false)->count(),
            'keluarga' => PasienPmo::where('jenis_pmo', 'Keluarga')->where('is_active', true)->count(),
            'kader'    => PasienPmo::where('jenis_pmo', 'Kader')->where('is_active', true)->count(),
        ];
    }
}
