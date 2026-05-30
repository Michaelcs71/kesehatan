<?php

namespace Database\Seeders;

use App\Models\MasterKategoriObat;
use Illuminate\Database\Seeder;

class MasterKategoriObatSeeder extends Seeder
{
    /**
     * Seed default kategori obat (migrasi dari enum hardcode lama)
     */
    public function run(): void
    {
        $defaults = [
            [
                'nama'      => 'Oral',
                'deskripsi' => 'Obat yang diminum melalui mulut (tablet, kapsul, sirup, dll)',
            ],
            [
                'nama'      => 'Injeksi',
                'deskripsi' => 'Obat yang diberikan melalui suntikan',
            ],
            [
                'nama'      => 'Insulin',
                'deskripsi' => 'Hormon insulin untuk pengobatan diabetes',
            ],
            [
                'nama'      => 'Lainnya',
                'deskripsi' => 'Kategori obat lain yang belum terdefinisi',
            ],
        ];

        foreach ($defaults as $data) {
            MasterKategoriObat::firstOrCreate(
                ['nama' => $data['nama']],
                array_merge($data, ['is_active' => true])
            );
        }

        $this->command->info('  [OK] ' . count($defaults) . ' default kategori obat seeded');
    }
}