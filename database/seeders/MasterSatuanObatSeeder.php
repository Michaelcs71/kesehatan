<?php

namespace Database\Seeders;

use App\Models\MasterSatuanObat;
use Illuminate\Database\Seeder;

class MasterSatuanObatSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['nama' => 'Tablet', 'singkatan' => 'tab', 'deskripsi' => 'Sediaan padat berbentuk pipih bulat'],
            ['nama' => 'Kapsul', 'singkatan' => 'kap', 'deskripsi' => 'Sediaan padat dalam cangkang gelatin'],
            ['nama' => 'ml',     'singkatan' => 'ml',  'deskripsi' => 'Mililiter (untuk sediaan cair)'],
            ['nama' => 'mg',     'singkatan' => 'mg',  'deskripsi' => 'Miligram (satuan berat)'],
            ['nama' => 'IU',     'singkatan' => 'IU',  'deskripsi' => 'International Unit (untuk insulin/hormon)'],
            ['nama' => 'Sachet', 'singkatan' => 'sct', 'deskripsi' => 'Kemasan kantung kecil (bubuk/granul)'],
        ];

        foreach ($defaults as $data) {
            MasterSatuanObat::firstOrCreate(
                ['nama' => $data['nama']],
                array_merge($data, ['is_active' => true])
            );
        }

        $this->command->info('  [OK] '.count($defaults).' default satuan obat seeded');
    }
}
