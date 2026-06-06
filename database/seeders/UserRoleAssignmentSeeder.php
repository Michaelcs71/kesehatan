<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserRoleAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('===========================================');
        $this->command->info('Assigning Spatie roles to existing users...');
        $this->command->info('===========================================');

        // Mapping email -> Spatie role
        $assignments = [
            'superadmin@kesehatan.test' => 'superadmin',
            'admin@kesehatan.test' => 'admin',
            'pmo@kesehatan.test' => 'pmo',
            'pasien@kesehatan.test' => 'pasien',
        ];

        foreach ($assignments as $email => $roleName) {
            $user = User::where('email', $email)->first();

            if (! $user) {
                $this->command->warn("  - SKIP: user {$email} tidak ditemukan");

                continue;
            }

            // Sync (replace) Spatie role
            $user->syncRoles([$roleName]);

            $this->command->info("  - {$email} -> role: {$roleName}");
        }

        $this->command->info('');
        $this->command->info('Done.');
        $this->command->info('===========================================');
    }
}
