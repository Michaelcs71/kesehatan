<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_cgd_peserta', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('jadwal_cgd_id')->constrained('jadwal_cgds')->cascadeOnDelete();
            $table->foreignUuid('id_pasien_pmo')->constrained('pasien_pmos');

            $table->string('nama_pasien');                 // snapshot
            $table->string('nama_pmo')->nullable();        // snapshot

            $table->dateTime('dikirim_dibuat_pada')->nullable();
            $table->dateTime('dikirim_h1_pada')->nullable();

            $table->timestamps();

            $table->unique(['jadwal_cgd_id', 'id_pasien_pmo'], 'uq_cgd_peserta');
            $table->index('dikirim_dibuat_pada');
            $table->index('dikirim_h1_pada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_cgd_peserta');
    }
};
