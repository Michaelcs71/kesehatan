<?php

namespace App\Services;

use App\Models\JadwalMinumObat;
use App\Models\MasterObat;
use App\Models\User;
use App\Repos\JadwalMinumObatRepository;
use Illuminate\Support\Facades\Auth;

class JadwalMinumObatService
{
    /**
     * Get all jadwal — auto filter per role
     */
    public static function getAllJadwal(array $params): array
    {
        /** @var User $loggedInUser */
        $loggedInUser = Auth::user();

        // Setup filter berdasarkan role
        $forPmoUserId = null;
        $forPasienUserId = null;

        if ($loggedInUser->isPmo()) {
            $forPmoUserId = $loggedInUser->id;
        } elseif ($loggedInUser->isPasien()) {
            $forPasienUserId = $loggedInUser->id;
        }
        // Admin/Superadmin: tidak filter, lihat semua

        $data = JadwalMinumObatRepository::getAllJadwal(
            search: $params['search'] ?? '',
            status: $params['status'] ?? null,
            pasienPmoId: $params['id_pasien_pmo'] ?? null,
            obatId: $params['obat_id'] ?? null,
            createdBy: $params['created_by'] ?? null,
            forPmoUserId: $forPmoUserId,
            forPasienUserId: $forPasienUserId,
            page: ($params['pagenum'] ?? 0) + 1,
            perPage: $params['pagesize'] ?? 10,
        );

        return [
            'TotalRows' => $data->total(),
            'Rows' => $data->items(),
        ];
    }

    public static function findJadwalById(string $id): ?JadwalMinumObat
    {
        if (empty($id) || in_array($id, ['create', 'edit'])) {
            return null;
        }

        return JadwalMinumObatRepository::findJadwalById($id);
    }

    /**
     * Get options pasien-PMO untuk dropdown
     * Filter per role:
     * - Admin/Superadmin: lihat semua mapping aktif
     * - PMO: lihat mapping yang dia damping
     * - Pasien: lihat mapping dirinya sendiri
     */
    public static function getPasienPmoOptions(): array
    {
        /** @var User $loggedInUser */
        $loggedInUser = Auth::user();

        $pmoUserId = null;
        $pasienUserId = null;

        if ($loggedInUser->isPmo()) {
            $pmoUserId = $loggedInUser->id;
        } elseif ($loggedInUser->isPasien()) {
            $pasienUserId = $loggedInUser->id;
        }

        return JadwalMinumObatRepository::getPasienPmoOptions($pmoUserId, $pasienUserId);
    }

    public static function getObatOptions(?string $search = null): array
    {
        return JadwalMinumObatRepository::getObatOptions($search);
    }

    /**
     * Bulk create jadwal: 1 mapping → multi obat
     */
    public static function bulkCreate(array $data): array
    {
        /** @var User $loggedInUser */
        $loggedInUser = Auth::user();

        $auditData = ['created_by' => $loggedInUser->id];

        $created = JadwalMinumObatRepository::bulkCreate(
            pasienPmoId: $data['id_pasien_pmo'],
            obatItems: $data['obats'],
            auditData: $auditData,
        );

        return [
            'count' => count($created),
            'jadwals' => $created,
        ];
    }

    public static function updateJadwal(string $id, array $data): bool
    {
        /** @var User $loggedInUser */
        $loggedInUser = Auth::user();

        $jadwal = JadwalMinumObatRepository::findJadwalById($id);
        if (! $jadwal) {
            throw new \Exception('Jadwal tidak ditemukan.');
        }

        // Authorization: PMO/Pasien cuma boleh edit jadwal yang relevant
        self::authorizeAccess($jadwal, $loggedInUser);

        $data['updated_by'] = $loggedInUser->id;

        return JadwalMinumObatRepository::updateJadwal($id, $data);
    }

    public static function deactivate(string $id): bool
    {
        /** @var User $loggedInUser */
        $loggedInUser = Auth::user();

        $jadwal = JadwalMinumObatRepository::findJadwalById($id);
        if (! $jadwal) {
            throw new \Exception('Jadwal tidak ditemukan.');
        }

        self::authorizeAccess($jadwal, $loggedInUser);

        return JadwalMinumObatRepository::deactivate($id, $loggedInUser->id);
    }

    public static function activate(string $id): bool
    {
        /** @var User $loggedInUser */
        $loggedInUser = Auth::user();

        $jadwal = JadwalMinumObatRepository::findJadwalById($id);
        if (! $jadwal) {
            throw new \Exception('Jadwal tidak ditemukan.');
        }

        self::authorizeAccess($jadwal, $loggedInUser);

        return JadwalMinumObatRepository::activate($id, $loggedInUser->id);
    }

    public static function markSelesai(string $id): bool
    {
        /** @var User $loggedInUser */
        $loggedInUser = Auth::user();

        $jadwal = JadwalMinumObatRepository::findJadwalById($id);
        if (! $jadwal) {
            throw new \Exception('Jadwal tidak ditemukan.');
        }

        self::authorizeAccess($jadwal, $loggedInUser);

        return JadwalMinumObatRepository::markSelesai($id, $loggedInUser->id);
    }

    public static function deleteJadwal(string $id): bool
    {
        /** @var User $loggedInUser */
        $loggedInUser = Auth::user();

        $jadwal = JadwalMinumObatRepository::findJadwalById($id);
        if (! $jadwal) {
            throw new \Exception('Jadwal tidak ditemukan.');
        }

        self::authorizeAccess($jadwal, $loggedInUser);

        return JadwalMinumObatRepository::deleteJadwal($id, $loggedInUser->id);
    }

    /**
     * Quick-create obat dari form jadwal
     */
    public static function quickCreateObat(array $data): MasterObat
    {
        /** @var User $loggedInUser */
        $loggedInUser = Auth::user();

        return JadwalMinumObatRepository::quickCreateObat($data, $loggedInUser->id);
    }

    public static function getStats(): array
    {
        /** @var User $loggedInUser */
        $loggedInUser = Auth::user();

        $base = JadwalMinumObat::query();

        // Apply role filter
        if ($loggedInUser->isPmo()) {
            $base->whereHas('pasienPmo', fn ($q) => $q->where('pmo_user_id', $loggedInUser->id));
        } elseif ($loggedInUser->isPasien()) {
            $base->whereHas('pasienPmo', fn ($q) => $q->where('id_user', $loggedInUser->id));
        }

        return [
            'total' => (clone $base)->count(),
            'aktif' => (clone $base)->where('status', 'aktif')->count(),
            'nonaktif' => (clone $base)->where('status', 'nonaktif')->count(),
            'selesai' => (clone $base)->where('status', 'selesai')->count(),
        ];
    }

    /**
     * Authorization check: user boleh akses jadwal ini?
     */
    protected static function authorizeAccess(JadwalMinumObat $jadwal, User $user): void
    {
        if ($user->isSuperadmin() || $user->isAdmin()) {
            return; // bebas
        }

        $mapping = $jadwal->pasienPmo;
        if (! $mapping) {
            throw new \Exception('Mapping tidak ditemukan.');
        }

        if ($user->isPmo() && $mapping->pmo_user_id !== $user->id) {
            throw new \Exception('Anda tidak punya akses ke jadwal ini.');
        }

        if ($user->isPasien() && $mapping->id_user !== $user->id) {
            throw new \Exception('Anda tidak punya akses ke jadwal ini.');
        }
    }
}
