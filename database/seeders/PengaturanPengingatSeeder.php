<?php

namespace Database\Seeders;

use App\Models\PengaturanPengingat;
use Illuminate\Database\Seeder;

class PengaturanPengingatSeeder extends Seeder
{
    public function run(): void
    {
        // Idempoten: hanya buat bila belum ada baris.
        if (PengaturanPengingat::query()->doesntExist()) {
            PengaturanPengingat::create(PengaturanPengingat::defaults());
        }
    }
}
