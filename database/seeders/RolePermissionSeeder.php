<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    private array $permissions = [
        // ===== MASTER OBAT =====
        'master-obat.index',
        'master-obat.show',
        'master-obat.create',
        'master-obat.edit',
        'master-obat.delete',
        'master-obat.verify',

        // ===== MASTER KATEGORI OBAT =====
        'master-kategori-obat.index',
        'master-kategori-obat.show',
        'master-kategori-obat.create',
        'master-kategori-obat.edit',
        'master-kategori-obat.delete',

        // ===== MASTER SATUAN OBAT =====
        'master-satuan-obat.index',
        'master-satuan-obat.show',
        'master-satuan-obat.create',
        'master-satuan-obat.edit',
        'master-satuan-obat.delete',

        // ===== MASTER USER =====
        'master-user.index',
        'master-user.show',
        'master-user.create',
        'master-user.edit',
        'master-user.delete',

        // ===== PASIEN PMO MAPPING =====
        'pasien-pmo.index',
        'pasien-pmo.show',
        'pasien-pmo.create',
        'pasien-pmo.edit',
        'pasien-pmo.delete',

        // ===== PLACEHOLDER untuk modul masa depan =====
        'master-pasien.index',
        'master-pasien.show',
        'master-pasien.create',
        'master-pasien.edit',
        'master-pasien.delete',

        'master-pmo.index',
        'master-pmo.show',
        'master-pmo.create',
        'master-pmo.edit',
        'master-pmo.delete',

        'master-admin.index',
        'master-admin.show',
        'master-admin.create',
        'master-admin.edit',
        'master-admin.delete',

        'pasien-binaan.index',
        'pasien-binaan.show',

        // ===== JADWAL MINUM OBAT =====
        'jadwal-mo.index',
        'jadwal-mo.show',
        'jadwal-mo.create',
        'jadwal-mo.edit',
        'jadwal-mo.delete',

        'jadwal-cgd.index',
        'jadwal-cgd.show',
        'jadwal-cgd.create',
        'jadwal-cgd.edit',
        'jadwal-cgd.delete',

        // ===== PENGINGAT MINUM OBAT (LOG) =====
        'pengingat-mo.index',
        'pengingat-mo.show',
        'pengingat-mo.create',
        'pengingat-mo.edit',
        'pengingat-mo.delete',

        // ===== PENGINGAT CEK GULA DARAH (LOG) =====
        'pengingat-cgd.index',
        'pengingat-cgd.show',
        'pengingat-cgd.create',
        'pengingat-cgd.edit',
        'pengingat-cgd.delete',

        'riwayat.index',

        'konten-pengumuman.index',
        'konten-pengumuman.create',
        'konten-pengumuman.edit',
        'konten-pengumuman.delete',

        'konten-edukasi.index',
        'konten-edukasi.create',
        'konten-edukasi.edit',
        'konten-edukasi.delete',

        'konten-galery.index',
        'konten-galery.create',
        'konten-galery.edit',
        'konten-galery.delete',

        'laporan-kepatuhan.index',
    ];

    private array $rolePermissions = [
        'pasien' => [
            'master-obat.index',
            'master-obat.show',
            'master-obat.create',
            'master-obat.edit',
            'jadwal-mo.index',
            'jadwal-mo.show',
            'jadwal-mo.create',
            'jadwal-mo.edit',
            'jadwal-cgd.index',
            'jadwal-cgd.show',
            // Pengingat MO (bantu konfirmasi)
            'pengingat-mo.index',
            'pengingat-mo.show',
            'pengingat-mo.create',
            'pengingat-mo.edit',
            'pengingat-mo.delete',

            // Pengingat CGD (konfirmasi sendiri)
            'pengingat-cgd.index',
            'pengingat-cgd.show',
            'pengingat-cgd.create',
            'pengingat-cgd.edit',
            'pengingat-cgd.delete',

            'riwayat.index',
        ],

        'pmo' => [
            'master-obat.index',
            'master-obat.show',
            'master-obat.create',
            'master-obat.edit',
            'pasien-binaan.index',
            'pasien-binaan.show',
            // Jadwal Minum Obat
            'jadwal-mo.index',
            'jadwal-mo.show',
            'jadwal-mo.create',
            'jadwal-mo.edit',
            'jadwal-mo.delete',
            'jadwal-cgd.index',
            'jadwal-cgd.show',
            // Pengingat MO (bantu konfirmasi)
            'pengingat-mo.index',
            'pengingat-mo.show',
            'pengingat-mo.create',
            'pengingat-mo.edit',
            'pengingat-mo.delete',

            // Pengingat CGD (bantu konfirmasi)
            'pengingat-cgd.index',
            'pengingat-cgd.show',
            'pengingat-cgd.create',
            'pengingat-cgd.edit',
            'pengingat-cgd.delete',

            'riwayat.index',
        ],

        'admin' => [
            // Master User
            'master-user.index',
            'master-user.show',
            'master-user.create',
            'master-user.edit',
            'master-user.delete',

            // Pasien PMO Mapping
            'pasien-pmo.index',
            'pasien-pmo.show',
            'pasien-pmo.create',
            'pasien-pmo.edit',
            'pasien-pmo.delete',

            // Master Obat
            'master-obat.index',
            'master-obat.show',
            'master-obat.create',
            'master-obat.edit',
            'master-obat.delete',
            'master-obat.verify',
            // Master Kategori Obat (NEW)
            'master-kategori-obat.index',
            'master-kategori-obat.show',
            'master-kategori-obat.create',
            'master-kategori-obat.edit',
            'master-kategori-obat.delete',
            // Master Satuan Obat (NEW)
            'master-satuan-obat.index',
            'master-satuan-obat.show',
            'master-satuan-obat.create',
            'master-satuan-obat.edit',
            'master-satuan-obat.delete',
            // Master Pasien
            'master-pasien.index',
            'master-pasien.show',
            'master-pasien.create',
            'master-pasien.edit',
            'master-pasien.delete',
            // Master PMO
            'master-pmo.index',
            'master-pmo.show',
            'master-pmo.create',
            'master-pmo.edit',
            'master-pmo.delete',
            // Transaksi
            // Jadwal Minum Obat
            'jadwal-mo.index',
            'jadwal-mo.show',
            'jadwal-mo.create',
            'jadwal-mo.edit',
            'jadwal-mo.delete',

            'jadwal-cgd.index',
            'jadwal-cgd.show',
            'jadwal-cgd.create',
            'jadwal-cgd.edit',
            'jadwal-cgd.delete',

            // Pengingat MO (bantu konfirmasi)
            'pengingat-mo.index',
            'pengingat-mo.show',
            'pengingat-mo.create',
            'pengingat-mo.edit',
            'pengingat-mo.delete',

            // Pengingat CGD (bantu konfirmasi)
            'pengingat-cgd.index',
            'pengingat-cgd.show',
            'pengingat-cgd.create',
            'pengingat-cgd.edit',
            'pengingat-cgd.delete',

            // Konten
            'konten-pengumuman.index',
            'konten-pengumuman.create',
            'konten-pengumuman.edit',
            'konten-pengumuman.delete',
            'konten-edukasi.index',
            'konten-edukasi.create',
            'konten-edukasi.edit',
            'konten-edukasi.delete',
            'konten-galery.index',
            'konten-galery.create',
            'konten-galery.edit',
            'konten-galery.delete',
            // Laporan
            'laporan-kepatuhan.index',
        ],

        'superadmin' => '*',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('');
        $this->command->info('===========================================');
        $this->command->info('Creating permissions...');
        $this->command->info('===========================================');

        foreach ($this->permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        $this->command->info('  Total permissions: ' . count($this->permissions));

        $this->command->info('');
        $this->command->info('Creating roles & assigning permissions...');

        foreach ($this->rolePermissions as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            if ($perms === '*') {
                $allPerms = Permission::pluck('name')->toArray();
                $role->syncPermissions($allPerms);
                $this->command->info("  - {$roleName}: " . count($allPerms) . ' permissions (ALL)');
            } else {
                $role->syncPermissions($perms);
                $this->command->info("  - {$roleName}: " . count($perms) . ' permissions');
            }
        }

        $this->command->info('');
        $this->command->info('Syncing existing users with Spatie roles...');
        $userCount = 0;
        \App\Models\User::all()->each(function ($user) use (&$userCount) {
            if ($user->role) {
                $user->syncRoles([$user->role->value]);
                // Clear direct permissions — hak akses MURNI dari role
                $user->syncPermissions([]);
                $userCount++;
            }
        });
        $this->command->info("  - {$userCount} users synced (roles + cleared direct perms)");

        $this->command->info('');
        $this->command->info('Roles & permissions seeded successfully.');
        $this->command->info('===========================================');
    }
}
