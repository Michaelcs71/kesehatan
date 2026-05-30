<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_minum_obats', function (Blueprint $table) {
            $table->dropColumn('durasi_hari');
        });
    }

    public function down(): void
    {
        Schema::table('jadwal_minum_obats', function (Blueprint $table) {
            $table->integer('durasi_hari')->nullable()->after('frekuensi_per_hari');
        });
    }
};
