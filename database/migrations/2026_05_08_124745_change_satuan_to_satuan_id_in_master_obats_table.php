<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah column satuan_id
        Schema::table('master_obats', function (Blueprint $table) {
            $table->foreignUuid('satuan_id')->nullable()->after('dosis_default')
                ->constrained('master_satuan_obats')
                ->nullOnDelete();
        });

        // 2. Migrasi data: map satuan string lama ke satuan_id baru
        // Lookup case-insensitive (e.g., enum value "tablet" lowercase -> "Tablet" di DB)
        $satuanMap = DB::table('master_satuan_obats')
            ->whereNull('deleted_at')
            ->get()
            ->mapWithKeys(fn ($s) => [strtolower($s->nama) => $s->id])
            ->toArray();

        // Default fallback ke "Tablet" kalau tidak ketemu
        $defaultId = $satuanMap['tablet'] ?? null;

        $obats = DB::table('master_obats')->whereNull('deleted_at')->get();
        foreach ($obats as $obat) {
            $satuanLower = strtolower($obat->satuan ?? '');
            $satuanId = $satuanMap[$satuanLower] ?? $defaultId;

            if ($satuanId) {
                DB::table('master_obats')
                    ->where('id', $obat->id)
                    ->update(['satuan_id' => $satuanId]);
            }
        }

        // 3. Drop column satuan lama
        Schema::table('master_obats', function (Blueprint $table) {
            $table->dropColumn('satuan');
        });
    }

    public function down(): void
    {
        // 1. Tambah kembali column satuan
        Schema::table('master_obats', function (Blueprint $table) {
            $table->string('satuan', 50)->nullable()->after('dosis_default');
        });

        // 2. Reverse migrasi
        $satuanMap = DB::table('master_satuan_obats')
            ->whereNull('deleted_at')
            ->get()
            ->mapWithKeys(fn ($s) => [$s->id => strtolower($s->nama)])
            ->toArray();

        $obats = DB::table('master_obats')->whereNull('deleted_at')->whereNotNull('satuan_id')->get();
        foreach ($obats as $obat) {
            $satuanName = $satuanMap[$obat->satuan_id] ?? 'tablet';
            DB::table('master_obats')
                ->where('id', $obat->id)
                ->update(['satuan' => $satuanName]);
        }

        // 3. Drop foreign key + column satuan_id
        Schema::table('master_obats', function (Blueprint $table) {
            $table->dropForeign(['satuan_id']);
            $table->dropColumn('satuan_id');
        });
    }
};
