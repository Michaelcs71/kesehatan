<?php

namespace App\Helpers;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionHelper
{
    /**
     * Module metadata: mapping permission prefix -> {label, icon, color}
     */
    public static function getModuleMetadata(): array
    {
        return [
            'master-user' => [
                'label' => 'Master User',
                'icon' => 'ri-team-line',
                'color' => 'primary',
                'order' => 10,
            ],
            'pasien-pmo' => [
                'label' => 'Pasien PMO Mapping',
                'icon' => 'ri-links-line',
                'color' => 'success',
                'order' => 35,
            ],
            'master-obat' => [
                'label' => 'Master Obat',
                'icon' => 'ri-medicine-bottle-line',
                'color' => 'success',
                'order' => 20,
            ],
            'master-pasien' => [
                'label' => 'Master Pasien',
                'icon' => 'ri-user-line',
                'color' => 'info',
                'order' => 30,
            ],
            'master-pmo' => [
                'label' => 'Master PMO',
                'icon' => 'ri-team-fill',
                'color' => 'info',
                'order' => 40,
            ],
            'master-admin' => [
                'label' => 'Master Admin',
                'icon' => 'ri-shield-user-line',
                'color' => 'danger',
                'order' => 50,
            ],
            'pasien-binaan' => [
                'label' => 'Pasien Binaan',
                'icon' => 'ri-heart-pulse-line',
                'color' => 'info',
                'order' => 60,
            ],
            'jadwal-mo' => [
                'label' => 'Jadwal Minum Obat',
                'icon' => 'ri-calendar-check-line',
                'color' => 'warning',
                'order' => 70,
            ],
            'jadwal-cgd' => [
                'label' => 'Jadwal Cek Gula Darah',
                'icon' => 'ri-test-tube-line',
                'color' => 'warning',
                'order' => 80,
            ],
            'pengingat-mo' => [
                'label' => 'Pengingat Minum Obat',
                'icon' => 'ri-notification-line',
                'color' => 'secondary',
                'order' => 90,
            ],
            'pengingat-cgd' => [
                'label' => 'Pengingat Cek Gula Darah',
                'icon' => 'ri-alarm-line',
                'color' => 'secondary',
                'order' => 100,
            ],
            'riwayat' => [
                'label' => 'Riwayat',
                'icon' => 'ri-history-line',
                'color' => 'dark',
                'order' => 110,
            ],
            'konten-pengumuman' => [
                'label' => 'Konten Pengumuman',
                'icon' => 'ri-megaphone-line',
                'color' => 'primary',
                'order' => 120,
            ],
            'konten-edukasi' => [
                'label' => 'Konten Edukasi',
                'icon' => 'ri-book-open-line',
                'color' => 'primary',
                'order' => 130,
            ],
            'konten-galery' => [
                'label' => 'Konten Galery',
                'icon' => 'ri-image-line',
                'color' => 'primary',
                'order' => 140,
            ],
            'laporan-kepatuhan' => [
                'label' => 'Laporan Kepatuhan',
                'icon' => 'ri-bar-chart-line',
                'color' => 'success',
                'order' => 150,
            ],
        ];
    }

    public static function getActionLabels(): array
    {
        return [
            'index' => 'Lihat List',
            'show' => 'Lihat Detail',
            'create' => 'Tambah Baru',
            'edit' => 'Edit',
            'delete' => 'Hapus',
            'verify' => 'Verifikasi',
        ];
    }

    /**
     * Get all permissions grouped by module with metadata
     */
    public static function getGroupedPermissions(): array
    {
        $allPermissions = Permission::orderBy('name')->get();
        $metadata = self::getModuleMetadata();
        $actionLabels = self::getActionLabels();

        $grouped = [];

        foreach ($allPermissions as $perm) {
            $parts = explode('.', $perm->name);
            $action = array_pop($parts);
            $module = implode('.', $parts);

            if (! isset($grouped[$module])) {
                $meta = $metadata[$module] ?? [
                    'label' => ucwords(str_replace('-', ' ', $module)),
                    'icon' => 'ri-folder-line',
                    'color' => 'secondary',
                    'order' => 999,
                ];

                $grouped[$module] = [
                    'key' => $module,
                    'label' => $meta['label'],
                    'icon' => $meta['icon'],
                    'color' => $meta['color'],
                    'order' => $meta['order'],
                    'permissions' => [],
                ];
            }

            $grouped[$module]['permissions'][] = [
                'name' => $perm->name,
                'action' => $action,
                'label' => $actionLabels[$action] ?? ucfirst($action),
            ];
        }

        uasort($grouped, fn ($a, $b) => $a['order'] <=> $b['order']);

        return array_values($grouped);
    }

    /**
     * Get user's permission summary
     *
     * Output:
     * [
     *     'is_overridden' => bool,           // mode override aktif?
     *     'role' => 'admin',
     *     'role_label' => 'Admin',
     *     'role_permissions' => [...],       // permissions dari role (info-only)
     *     'direct_permissions' => [...],     // direct permissions
     *     'effective_permissions' => [...],  // YANG BENAR-BENAR DIPAKAI (depends on mode)
     *     'role_count' => 13,
     *     'direct_count' => 0,
     *     'effective_count' => 13,
     * ]
     */
    public static function getUserPermissionSummary(User $user): array
    {
        $roleName = $user->role?->value;

        // Permissions dari role (info)
        $rolePerms = collect();
        if ($roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $rolePerms = $role->permissions->pluck('name');
            }
        }

        // Direct permissions
        $directPerms = $user->getDirectPermissions()->pluck('name');

        // Effective permissions: tergantung mode
        if ($user->permission_overridden) {
            // Override mode: only direct
            $effectivePerms = $directPerms;
        } else {
            // Default: union role + direct
            $effectivePerms = $rolePerms->merge($directPerms)->unique()->values();
        }

        return [
            'is_overridden' => (bool) $user->permission_overridden,
            'role' => $roleName,
            'role_label' => $user->role?->label() ?? '-',
            'role_permissions' => $rolePerms->values()->toArray(),
            'direct_permissions' => $directPerms->values()->toArray(),
            'effective_permissions' => $effectivePerms->values()->toArray(),
            'role_count' => $rolePerms->count(),
            'direct_count' => $directPerms->count(),
            'effective_count' => $effectivePerms->count(),
        ];
    }

    /**
     * Get user permissions grouped (untuk display di show page)
     */
    public static function getUserPermissionsGrouped(User $user): array
    {
        $summary = self::getUserPermissionSummary($user);
        $grouped = self::getGroupedPermissions();

        $effectiveLookup = array_flip($summary['effective_permissions']);

        foreach ($grouped as &$group) {
            $group['user_permissions'] = [];
            foreach ($group['permissions'] as $perm) {
                if (isset($effectiveLookup[$perm['name']])) {
                    $group['user_permissions'][] = $perm;
                }
            }
        }

        return array_values(array_filter($grouped, fn ($g) => count($g['user_permissions']) > 0));
    }
}
