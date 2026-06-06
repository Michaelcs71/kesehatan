<?php

namespace Database\Seeders;

use App\Enums\StatusObat;
use App\Enums\UserRole;
use App\Models\MasterKategoriObat;
use App\Models\MasterObat;
use App\Models\MasterSatuanObat;
use App\Models\User;
use Illuminate\Database\Seeder;

class MasterObatSeeder extends Seeder
{
    /**
     * Seed 5 data dummy obat (untuk pengujian master obat).
     * Kategori & satuan dirujuk berdasarkan nama, pastikan
     * MasterKategoriObatSeeder & MasterSatuanObatSeeder sudah dijalankan.
     */
    public function run(): void
    {
        // Pastikan kategori & satuan tersedia
        $this->callOnce([
            MasterKategoriObatSeeder::class,
            MasterSatuanObatSeeder::class,
        ]);

        // created_by wajib diisi — pakai superadmin/admin sebagai pembuat
        $creator = User::where('role', UserRole::SUPERADMIN->value)->first()
            ?? User::where('role', UserRole::ADMIN->value)->first()
            ?? User::first();

        if (! $creator) {
            $this->command->warn('  [SKIP] Tidak ada user untuk created_by, jalankan UserSeeder dulu');

            return;
        }

        $defaults = [
            [
                'nama' => 'Metformin',
                'kategori' => 'Oral',
                'dosis_default' => '500 mg',
                'satuan' => 'Tablet',
                'deskripsi' => 'Obat antidiabetes lini pertama untuk menurunkan kadar gula darah.',
                'aturan_minum' => '2x sehari sesudah makan',
                'efek_samping' => 'Mual, diare, gangguan pencernaan ringan',
                'kontraindikasi' => 'Gangguan fungsi ginjal berat, asidosis laktat',
            ],
            [
                'nama' => 'Glibenklamid',
                'kategori' => 'Oral',
                'dosis_default' => '5 mg',
                'satuan' => 'Tablet',
                'deskripsi' => 'Golongan sulfonilurea yang merangsang produksi insulin pankreas.',
                'aturan_minum' => '1x sehari sebelum sarapan',
                'efek_samping' => 'Hipoglikemia, kenaikan berat badan',
                'kontraindikasi' => 'Diabetes tipe 1, ketoasidosis diabetik',
            ],
            [
                'nama' => 'Insulin Glargine',
                'kategori' => 'Insulin',
                'dosis_default' => '10 IU',
                'satuan' => 'IU',
                'deskripsi' => 'Insulin kerja panjang untuk kontrol gula darah basal.',
                'aturan_minum' => '1x sehari pada malam hari (subkutan)',
                'efek_samping' => 'Hipoglikemia, reaksi di lokasi suntikan',
                'kontraindikasi' => 'Hipersensitivitas terhadap insulin glargine',
            ],
            [
                'nama' => 'Acarbose',
                'kategori' => 'Oral',
                'dosis_default' => '50 mg',
                'satuan' => 'Tablet',
                'deskripsi' => 'Menghambat penyerapan karbohidrat untuk menekan lonjakan gula darah.',
                'aturan_minum' => '3x sehari saat suapan pertama makan',
                'efek_samping' => 'Kembung, perut begah, diare',
                'kontraindikasi' => 'Penyakit radang usus, sirosis hati',
            ],
            [
                'nama' => 'Glukosa Oral',
                'kategori' => 'Lainnya',
                'dosis_default' => '15 mg',
                'satuan' => 'Sachet',
                'deskripsi' => 'Sediaan glukosa cepat untuk penanganan kondisi hipoglikemia.',
                'aturan_minum' => 'Saat gejala hipoglikemia muncul',
                'efek_samping' => 'Lonjakan gula darah bila berlebihan',
                'kontraindikasi' => 'Tidak ada kontraindikasi signifikan pada penggunaan darurat',
            ],
        ];

        $created = 0;
        foreach ($defaults as $data) {
            $kategori = MasterKategoriObat::where('nama', $data['kategori'])->first();
            $satuan = MasterSatuanObat::where('nama', $data['satuan'])->first();

            MasterObat::firstOrCreate(
                ['nama' => $data['nama']],
                [
                    'kategori_id' => $kategori?->id,
                    'satuan_id' => $satuan?->id,
                    'dosis_default' => $data['dosis_default'],
                    'deskripsi' => $data['deskripsi'],
                    'aturan_minum' => $data['aturan_minum'],
                    'efek_samping' => $data['efek_samping'],
                    'kontraindikasi' => $data['kontraindikasi'],
                    'status' => StatusObat::APPROVED,
                    'created_by' => $creator->id,
                    'verified_by' => $creator->id,
                    'verified_at' => now(),
                ]
            );
            $created++;
        }

        $this->command->info('  [OK] '.$created.' data dummy obat seeded');
    }
}
