<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Order matters!
            UserSeeder::class,                  // Bikin 4 default users
            RolePermissionSeeder::class,        // Bikin roles + permissions Spatie
            UserRoleAssignmentSeeder::class,    // Assign Spatie role ke 4 users
            KontenSeeder::class,                // Contoh konten publik (pengumuman, edukasi, galeri)
        ]);
    }
}
