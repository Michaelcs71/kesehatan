<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\PasienProfile;
use App\Models\PmoProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@kesehatan.test'],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'password' => Hash::make('password'),
                'role' => UserRole::SUPERADMIN->value,
                'whatsapp_number' => '081234567890',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@kesehatan.test'],
            [
                'name' => 'Admin Verifikator',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'role' => UserRole::ADMIN->value,
                'whatsapp_number' => '081234567891',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $pmoUser = User::updateOrCreate(
            ['email' => 'pmo@kesehatan.test'],
            [
                'name' => 'Budi PMO',
                'username' => 'pmo_budi',
                'password' => Hash::make('password'),
                'role' => UserRole::PMO->value,
                'whatsapp_number' => '081234567892',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        PmoProfile::updateOrCreate(
            ['user_id' => $pmoUser->id],
            [
                'jenis_kelamin' => 'L',
                'hubungan_dengan_pasien' => 'Anak',
                'kota' => 'Jakarta',
                'provinsi' => 'DKI Jakarta',
            ]
        );

        $pasienUser = User::updateOrCreate(
            ['email' => 'pasien@kesehatan.test'],
            [
                'name' => 'Siti Pasien',
                'username' => 'pasien_siti',
                'password' => Hash::make('password'),
                'role' => UserRole::PASIEN->value,
                'whatsapp_number' => '081234567893',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        PasienProfile::updateOrCreate(
            ['user_id' => $pasienUser->id],
            [
                'pmo_id' => $pmoUser->id,
                'jenis_kelamin' => 'P',
                'tipe_diabetes' => 'tipe_2',
                'tanggal_diagnosis' => now()->subYears(3),
                'tinggi_badan' => 158,
                'berat_badan' => 65,
                'kota' => 'Jakarta',
                'provinsi' => 'DKI Jakarta',
            ]
        );

        $this->command->info('');
        $this->command->info('===========================================');
        $this->command->info('Default users created:');
        $this->command->info('===========================================');
        $this->command->info('SUPERADMIN  : superadmin@kesehatan.test / password (username: superadmin)');
        $this->command->info('ADMIN       : admin@kesehatan.test      / password (username: admin)');
        $this->command->info('PMO         : pmo@kesehatan.test        / password (username: pmo_budi)');
        $this->command->info('PASIEN      : pasien@kesehatan.test     / password (username: pasien_siti)');
        $this->command->info('===========================================');
    }
}
