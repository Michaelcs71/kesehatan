<?php

namespace App\Repos;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    /**
     * Get all users with pagination + filters + visibility per role
     */
    public static function getAllUsers(
        string $search = '',
        string $role = '',
        ?string $isActive = null,
        int $page = 1,
        int $perPage = 10,
        ?User $loggedInUser = null
    ): LengthAwarePaginator {

        $query = User::query()->with(['biodata:id,user_id,nik,jenis_kelamin,tempat_lahir,tanggal_lahir']);

        // Visibility per role
        if ($loggedInUser && !$loggedInUser->isSuperadmin()) {
            $query->where(function (Builder $q) use ($loggedInUser) {
                $q->where('id', $loggedInUser->id)
                    ->orWhereIn('role', ['pmo', 'pasien', 'pengunjung']);
            });
        }

        // Filter role
        if ($role) {
            $query->where('role', $role);
        }

        // Filter status
        if ($isActive !== null && $isActive !== '') {
            $query->where('is_active', $isActive === '1' || $isActive === 'true');
        }

        // Search by name, username, whatsapp_number, or NIK (via biodata)
        if ($search) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('whatsapp_number', 'like', "%{$search}%")
                    ->orWhereHas('biodata', function ($qq) use ($search) {
                        $qq->where('nik', 'like', "%{$search}%");
                    });
            });
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    /**
     * Find user by ID with biodata
     */
    public static function findUserById(string $id): ?User
    {
        return User::with('biodata')->find($id);
    }

    /**
     * Create new user (data sudah dipisah: $userData, $biodataData)
     */
    public static function createUser(array $userData, array $biodataData = []): User
    {
        return DB::transaction(function () use ($userData, $biodataData) {
            // Hash password kalau ada
            if (!empty($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            }

            // Auto-generate username dari nama kalau tidak di-set
            if (empty($userData['username']) && !empty($userData['name'])) {
                $userData['username'] = self::generateUsername($userData['name']);
            }

            $user = User::create($userData);

            // Sync Spatie role
            if (!empty($userData['role'])) {
                $user->syncRoles([$userData['role']]);
            }

            // Save biodata kalau role pasien/pmo & data biodata ada
            if (in_array($userData['role'] ?? null, ['pasien', 'pmo']) && !empty($biodataData)) {
                $user->biodata()->create($biodataData);
            }

            return $user;
        });
    }

    /**
     * Update existing user (data sudah dipisah)
     */
    public static function updateUser(string $id, array $userData, array $biodataData = []): bool
    {
        return DB::transaction(function () use ($id, $userData, $biodataData) {
            $user = User::with('biodata')->find($id);
            if (!$user) {
                return false;
            }

            // Hash password kalau di-set baru, kalau kosong jangan diupdate
            if (!empty($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            } else {
                unset($userData['password']);
            }

            // Username tidak boleh diubah saat update (immutable)
            unset($userData['username']);

            $oldRole = $user->role->value;
            $newRole = $userData['role'] ?? $oldRole;

            $user->update($userData);

            // Sync Spatie role kalau ada
            if (!empty($userData['role'])) {
                $user->syncRoles([$userData['role']]);
            }

            // Handle biodata:
            // - Kalau role baru pasien/pmo: upsert biodata
            // - Kalau role baru bukan pasien/pmo: hapus biodata (kalau ada)
            $newRequiresBiodata = in_array($newRole, ['pasien', 'pmo']);

            if ($newRequiresBiodata && !empty($biodataData)) {
                if ($user->biodata) {
                    $user->biodata->update($biodataData);
                } else {
                    $user->biodata()->create($biodataData);
                }
            } elseif (!$newRequiresBiodata && $user->biodata) {
                $user->biodata->delete();
            }

            return true;
        });
    }

    /**
     * Soft delete user
     */
    public static function deleteUser(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $user = User::find($id);
            if (!$user) {
                return false;
            }
            return (bool) $user->delete();
        });
    }

    /**
     * Reset password user (admin only) - hash password baru & simpan
     */
    public static function resetPassword(string $id, string $newPassword): bool
    {
        return DB::transaction(function () use ($id, $newPassword) {
            $user = User::find($id);
            if (!$user) {
                return false;
            }
            $user->password = Hash::make($newPassword);
            return $user->save();
        });
    }

    /**
     * Generate username unik dari nama.
     */
    public static function generateUsername(string $name): string
    {
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '.', trim($name)));
        $base = trim($base, '.');

        $username = $base;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . $counter;
            $counter++;
        }
        return $username;
    }
}
