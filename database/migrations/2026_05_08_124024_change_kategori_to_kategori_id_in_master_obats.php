<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah column kategori_id (nullable dulu biar bisa migrasi data)
        Schema::table('master_obats', function (Blueprint $table) {
            $table->foreignUuid('kategori_id')->nullable()->after('nama')
                ->constrained('master_kategori_obats')
                ->nullOnDelete();
        });

        // 2. Migrasi data: map kategori string lama ke kategori_id baru
        $kategoriMap = DB::table('master_kategori_obats')
            ->whereNull('deleted_at')
            ->get()
            ->mapWithKeys(fn ($k) => [strtolower($k->nama) => $k->id])
            ->toArray();

        // Update tiap row master_obats
        $obats = DB::table('master_obats')->whereNull('deleted_at')->get();
        foreach ($obats as $obat) {
            $kategoriLower = strtolower($obat->kategori ?? 'lainnya');
            $kategoriId = $kategoriMap[$kategoriLower] ?? $kategoriMap['lainnya'] ?? null;

            if ($kategoriId) {
                DB::table('master_obats')
                    ->where('id', $obat->id)
                    ->update(['kategori_id' => $kategoriId]);
            }
        }

        // 3. Drop column kategori lama (yang string enum)
        Schema::table('master_obats', function (Blueprint $table) {
            $table->dropColumn('kategori');
        });

        // 4. (Optional) Buat kategori_id NOT NULL setelah data ter-migrate
        // Skipped: biarkan nullable supaya tidak break kalau ada edge case
    }

    public function down(): void
    {
        // 1. Tambah kembali column kategori (string)
        Schema::table('master_obats', function (Blueprint $table) {
            $table->string('kategori', 50)->nullable()->after('nama');
        });

        // 2. Reverse migrasi: kategori_id -> kategori string
        $kategoriMap = DB::table('master_kategori_obats')
            ->whereNull('deleted_at')
            ->get()
            ->mapWithKeys(fn ($k) => [$k->id => strtolower($k->nama)])
            ->toArray();

        $obats = DB::table('master_obats')->whereNull('deleted_at')->whereNotNull('kategori_id')->get();
        foreach ($obats as $obat) {
            $kategoriName = $kategoriMap[$obat->kategori_id] ?? 'lainnya';
            DB::table('master_obats')
                ->where('id', $obat->id)
                ->update(['kategori' => $kategoriName]);
        }

        // 3. Drop foreign key + column kategori_id
        Schema::table('master_obats', function (Blueprint $table) {
            $table->dropForeign(['kategori_id']);
            $table->dropColumn('kategori_id');
        });
    }
};
