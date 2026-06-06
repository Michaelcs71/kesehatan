<?php

namespace App\Repos;

use App\Models\PasienPmo;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PasienPmoRepository
{
    /**
     * Get all mappings dengan pagination + filter
     */
    public static function getAllMappings(
        string $search = '',
        ?string $isActive = null,
        ?string $jenisPmo = null,
        ?string $statusDiabetes = null,
        ?string $pmoUserId = null,
        int $page = 1,
        int $perPage = 10
    ): LengthAwarePaginator {

        $query = PasienPmo::query()
            ->with([
                'pasien:id,name,whatsapp_number',
                'pmo:id,name,whatsapp_number',
            ]);

        $query->search($search);

        if ($isActive !== null && $isActive !== '') {
            $query->where('is_active', $isActive === '1' || $isActive === 'true');
        }

        if (! empty($jenisPmo)) {
            $query->where('jenis_pmo', $jenisPmo);
        }

        if (! empty($statusDiabetes)) {
            $query->where('status_diabetes', $statusDiabetes);
        }

        if (! empty($pmoUserId)) {
            $query->where('pmo_user_id', $pmoUserId);
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    /**
     * Find mapping by ID dengan relasi
     */
    public static function findMappingById(string $id): ?PasienPmo
    {
        return PasienPmo::with([
            'pasien:id,name,whatsapp_number',
            'pmo:id,name,whatsapp_number',
            'creator:id,name',
            'updater:id,name',
        ])->find($id);
    }

    /**
     * Daftar PMO aktif untuk dropdown
     */
    public static function getPmoOptions(): array
    {
        return User::query()
            ->where('role', 'pmo')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'whatsapp_number'])
            ->toArray();
    }

    /**
     * Daftar pasien aktif yang BELUM punya mapping aktif (untuk dropdown create)
     * Kalau $excludeMappingId di-set, mapping itu dianggap "boleh muncul" (untuk edit mode)
     */
    public static function getPasienOptions(?string $excludeMappingId = null): array
    {
        // Subquery: ID pasien yang sudah punya mapping AKTIF
        $alreadyMappedIds = PasienPmo::query()
            ->where('is_active', true)
            ->when($excludeMappingId, fn ($q) => $q->where('id', '!=', $excludeMappingId))
            ->pluck('id_user')
            ->toArray();

        return User::query()
            ->where('role', 'pasien')
            ->where('is_active', true)
            ->whereNotIn('id', $alreadyMappedIds)
            ->with('biodata:id,user_id,nik')
            ->orderBy('name')
            ->get(['id', 'name', 'whatsapp_number'])
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'whatsapp_number' => $user->whatsapp_number,
                    'nik' => $user->biodata->nik ?? null,
                ];
            })
            ->toArray();
    }

    /**
     * Bulk create: 1 PMO → multiple pasien dengan status_diabetes per pasien
     *
     * @param  array  $pmoData  ['pmo_user_id' => uuid]
     * @param  array  $pasienItems  Array of ['pasien_id' => uuid, 'status_diabetes' => string]
     * @param  array  $commonData  ['jenis_pmo', 'tanggal_regis', 'catatan', 'created_by']
     */
    public static function bulkCreate(array $pmoData, array $pasienItems, array $commonData): array
    {
        return DB::transaction(function () use ($pmoData, $pasienItems, $commonData) {
            $created = [];

            // Load PMO
            $pmo = User::find($pmoData['pmo_user_id']);
            if (! $pmo) {
                throw new \Exception('PMO tidak ditemukan.');
            }

            // Load semua pasien sekaligus + biodata
            $pasienIds = array_column($pasienItems, 'pasien_id');
            $pasiens = User::with('biodata:id,user_id,nik')
                ->whereIn('id', $pasienIds)
                ->get()
                ->keyBy('id');

            foreach ($pasienItems as $item) {
                $pasien = $pasiens->get($item['pasien_id']);
                if (! $pasien) {
                    throw new \Exception("Pasien dengan ID {$item['pasien_id']} tidak ditemukan.");
                }

                $mapping = PasienPmo::create([
                    'id_user' => $pasien->id,
                    'pmo_user_id' => $pmo->id,
                    'nama_pasien' => $pasien->name,
                    'nik' => $pasien->biodata->nik ?? '',
                    'nama_pmo' => $pmo->name,
                    'jenis_pmo' => $commonData['jenis_pmo'],
                    'tanggal_regis' => $commonData['tanggal_regis'],
                    'status_diabetes' => $item['status_diabetes'],   // ← per pasien!
                    'is_active' => true,
                    'catatan' => $commonData['catatan'] ?? null,
                    'created_by' => $commonData['created_by'] ?? null,
                ]);

                $created[] = $mapping;
            }

            return $created;
        });
    }

    /**
     * Update single mapping (semua field bisa diubah)
     */
    public static function updateMapping(string $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $mapping = PasienPmo::find($id);
            if (! $mapping) {
                return false;
            }

            // Kalau pasien berubah, update snapshot
            if (! empty($data['id_user']) && $data['id_user'] !== $mapping->id_user) {
                $pasien = User::with('biodata:id,user_id,nik')->find($data['id_user']);
                if ($pasien) {
                    $data['nama_pasien'] = $pasien->name;
                    $data['nik'] = $pasien->biodata->nik ?? '';
                }
            }

            // Kalau PMO berubah, update snapshot
            if (! empty($data['pmo_user_id']) && $data['pmo_user_id'] !== $mapping->pmo_user_id) {
                $pmo = User::find($data['pmo_user_id']);
                if ($pmo) {
                    $data['nama_pmo'] = $pmo->name;
                }
            }

            return $mapping->update($data);
        });
    }

    /**
     * Set is_active = false (nonaktifkan)
     */
    public static function deactivate(string $id, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $mapping = PasienPmo::find($id);
            if (! $mapping) {
                return false;
            }

            return $mapping->update([
                'is_active' => false,
                'updated_by' => $userId,
            ]);
        });
    }

    /**
     * Set is_active = true (aktifkan kembali)
     */
    public static function activate(string $id, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $mapping = PasienPmo::find($id);
            if (! $mapping) {
                return false;
            }

            // Cek dulu apakah pasien sudah punya mapping aktif lain
            $hasActive = PasienPmo::where('id_user', $mapping->id_user)
                ->where('is_active', true)
                ->where('id', '!=', $id)
                ->exists();

            if ($hasActive) {
                throw new \Exception("Pasien {$mapping->nama_pasien} sudah punya mapping aktif lain. Nonaktifkan yang lain terlebih dahulu.");
            }

            return $mapping->update([
                'is_active' => true,
                'updated_by' => $userId,
            ]);
        });
    }

    /**
     * Soft delete
     */
    public static function deleteMapping(string $id, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $mapping = PasienPmo::find($id);
            if (! $mapping) {
                return false;
            }

            $mapping->deleted_by = $userId;
            $mapping->save();

            return (bool) $mapping->delete();
        });
    }
}
