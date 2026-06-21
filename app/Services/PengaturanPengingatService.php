<?php

namespace App\Services;

use App\Models\PengaturanPengingat;
use Illuminate\Support\Facades\Auth;

class PengaturanPengingatService
{
    /**
     * Ambil pengaturan; bila belum ada baris, kembalikan instance default
     * (tanpa menyimpan). Query ringan, dipanggil saat tick.
     */
    public static function get(): PengaturanPengingat
    {
        return PengaturanPengingat::query()->first()
            ?? new PengaturanPengingat(PengaturanPengingat::defaults());
    }

    /**
     * Simpan pengaturan (buat baris bila belum ada) + stamp updated_by.
     */
    public static function update(array $data): PengaturanPengingat
    {
        $pengaturan = PengaturanPengingat::query()->first() ?? new PengaturanPengingat;
        $pengaturan->fill($data);
        $pengaturan->updated_by = Auth::id();
        $pengaturan->save();

        return $pengaturan;
    }
}
