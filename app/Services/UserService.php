<?php

namespace App\Services;

use App\Helpers\PermissionHelper;
use App\Models\User;
use App\Repos\UserRepository;
use App\Services\Concerns\HasStandardizedMethods;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class UserService
{
    use HasStandardizedMethods;

    protected static function getEntityName(): string
    {
        return 'User';
    }

    protected static function getEntityPluralName(): string
    {
        return 'Users';
    }

    public static function getAllUsers(array $params): array
    {
        $loggedInUser = Auth::user();

        $data = UserRepository::getAllUsers(
            search: $params['search'] ?? '',
            role: $params['role'] ?? '',
            isActive: $params['is_active'] ?? null,
            page: ($params['pagenum'] ?? 0) + 1,
            perPage: $params['pagesize'] ?? 10,
            loggedInUser: $loggedInUser,
        );

        return [
            'TotalRows' => $data->total(),
            'Rows' => $data->items(),
        ];
    }

    public static function findUserById(string $id): ?User
    {
        if (empty($id) || in_array($id, ['create', 'edit'])) {
            return null;
        }

        return UserRepository::findUserById($id);
    }

    /**
     * Create new user (terima userData + biodataData)
     */
    public static function createUser(array $userData, array $biodataData = []): User
    {
        /** @var User $loggedInUser */
        $loggedInUser = Auth::user();

        if (! $loggedInUser->isSuperadmin()) {
            if (in_array($userData['role'] ?? '', ['admin', 'superadmin'])) {
                throw new \Exception('Anda tidak memiliki izin untuk membuat user dengan role admin/superadmin.');
            }
        }

        return UserRepository::createUser($userData, $biodataData);
    }

    /**
     * Update user (terima userData + biodataData)
     */
    public static function updateUser(string $id, array $userData, array $biodataData = []): bool
    {
        $target = UserRepository::findUserById($id);
        /** @var User $loggedInUser */
        $loggedInUser = Auth::user();

        if (! $target) {
            return false;
        }

        if (! $loggedInUser->isSuperadmin()) {
            $isSelf = $target->id === $loggedInUser->id;

            if (! $isSelf && in_array($target->role->value, ['admin', 'superadmin'])) {
                throw new \Exception('Anda tidak memiliki izin untuk mengubah user dengan role admin/superadmin.');
            }

            if ($isSelf && isset($userData['role']) && $userData['role'] !== $target->role->value) {
                throw new \Exception('Anda tidak dapat mengubah role akun Anda sendiri.');
            }

            if (! $isSelf && in_array($userData['role'] ?? '', ['admin', 'superadmin'])) {
                throw new \Exception('Anda tidak memiliki izin untuk memberikan role admin/superadmin.');
            }
        }

        return UserRepository::updateUser($id, $userData, $biodataData);
    }

    public static function deleteUser(string $id): bool
    {
        $target = UserRepository::findUserById($id);
        /** @var User $loggedInUser */
        $loggedInUser = Auth::user();

        if (! $target) {
            return false;
        }

        if ($target->id === $loggedInUser->id) {
            throw new \Exception('Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if (! $loggedInUser->isSuperadmin() && in_array($target->role->value, ['admin', 'superadmin'])) {
            throw new \Exception('Anda tidak memiliki izin untuk menghapus user dengan role admin/superadmin.');
        }

        return UserRepository::deleteUser($id);
    }

    public static function getAvailableRoles(): array
    {
        /** @var User $loggedInUser */
        $loggedInUser = Auth::user();

        if ($loggedInUser->isSuperadmin()) {
            return [
                'pengunjung' => 'Pengunjung',
                'pasien' => 'Pasien',
                'pmo' => 'PMO',
                'admin' => 'Admin',
                'superadmin' => 'Superadmin',
            ];
        }

        return [
            'pengunjung' => 'Pengunjung',
            'pasien' => 'Pasien',
            'pmo' => 'PMO',
        ];
    }

    public static function getStats(): array
    {
        /** @var User $loggedInUser */
        $loggedInUser = Auth::user();

        if ($loggedInUser->isSuperadmin()) {
            return [
                'total' => User::count(),
                'superadmin' => User::where('role', 'superadmin')->count(),
                'admin' => User::where('role', 'admin')->count(),
                'pmo' => User::where('role', 'pmo')->count(),
                'pasien' => User::where('role', 'pasien')->count(),
            ];
        }

        return [
            'total' => User::whereIn('role', ['pmo', 'pasien', 'pengunjung'])->count() + 1,
            'pmo' => User::where('role', 'pmo')->count(),
            'pasien' => User::where('role', 'pasien')->count(),
        ];
    }

    // ============================================================
    // PERMISSION MANAGEMENT (Superadmin only)
    // ============================================================

    public static function getPermissionEditorData(string $userId): array
    {
        /** @var User $loggedInUser */
        $loggedInUser = Auth::user();

        if (! $loggedInUser->isSuperadmin()) {
            throw new \Exception('Hanya superadmin yang dapat mengelola permission user.');
        }

        $user = UserRepository::findUserById($userId);
        if (! $user) {
            throw new \Exception('User tidak ditemukan.');
        }

        $summary = PermissionHelper::getUserPermissionSummary($user);
        $allGroupedPerms = PermissionHelper::getGroupedPermissions();
        $effectiveLookup = array_flip($summary['effective_permissions']);

        foreach ($allGroupedPerms as &$group) {
            foreach ($group['permissions'] as &$perm) {
                $perm['has'] = isset($effectiveLookup[$perm['name']]);
            }
        }

        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => $user->role?->value,
            'user_role_label' => $user->role?->label() ?? '-',
            'is_overridden' => (bool) $user->permission_overridden,
            'summary' => $summary,
            'grouped_permissions' => $allGroupedPerms,
        ];
    }

    public static function updateUserPermissions(string $userId, array $selectedPermissions): bool
    {
        /** @var User $loggedInUser */
        $loggedInUser = Auth::user();

        if (! $loggedInUser->isSuperadmin()) {
            throw new \Exception('Hanya superadmin yang dapat mengelola permission user.');
        }

        $user = UserRepository::findUserById($userId);
        if (! $user) {
            throw new \Exception('User tidak ditemukan.');
        }

        if ($user->id === $loggedInUser->id) {
            throw new \Exception('Anda tidak dapat mengubah permission akun Anda sendiri.');
        }

        return DB::transaction(function () use ($user, $selectedPermissions) {
            $validPerms = Permission::whereIn('name', $selectedPermissions)
                ->pluck('name')
                ->toArray();

            $user->enablePermissionOverride($validPerms);

            return true;
        });
    }

    public static function resetUserPermissions(string $userId): bool
    {
        /** @var User $loggedInUser */
        $loggedInUser = Auth::user();

        if (! $loggedInUser->isSuperadmin()) {
            throw new \Exception('Hanya superadmin yang dapat mereset permission user.');
        }

        $user = UserRepository::findUserById($userId);
        if (! $user) {
            throw new \Exception('User tidak ditemukan.');
        }

        if ($user->id === $loggedInUser->id) {
            throw new \Exception('Anda tidak dapat mereset permission akun Anda sendiri.');
        }

        return DB::transaction(function () use ($user) {
            $user->disablePermissionOverride();

            return true;
        });
    }

    // ============================================================
    // RESET PASSWORD (Admin only)
    // ============================================================

    /**
     * Reset password user (admin only)
     */
    public static function resetPassword(string $userId, string $newPassword): bool
    {
        $target = UserRepository::findUserById($userId);
        /** @var User $loggedInUser */
        $loggedInUser = Auth::user();

        if (! $target) {
            throw new \Exception('User tidak ditemukan.');
        }

        // Tidak boleh reset password diri sendiri (pakai fitur ubah password biasa)
        if ($target->id === $loggedInUser->id) {
            throw new \Exception('Untuk mengubah password sendiri, gunakan menu Profil.');
        }

        // Admin biasa tidak bisa reset password admin/superadmin
        if (! $loggedInUser->isSuperadmin() && in_array($target->role->value, ['admin', 'superadmin'])) {
            throw new \Exception('Anda tidak memiliki izin untuk reset password user admin/superadmin.');
        }

        return UserRepository::resetPassword($userId, $newPassword);
    }
}
