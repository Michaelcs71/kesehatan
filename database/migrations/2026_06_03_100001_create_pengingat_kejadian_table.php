<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengingat_kejadian', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('jenis', 10);              // 'mo' | 'cgd'
            $table->uuid('jadwal_id');                 // ke jadwal MO/CGD sesuai jenis
            $table->uuid('id_pasien_pmo')->nullable();
            $table->uuid('user_pasien_id')->nullable();
            $table->uuid('user_pmo_id')->nullable();

            $table->dateTime('waktu_jadwal');          // tanggal + slot jam (acuan selisih)
            $table->string('status', 15)->default('menunggu'); // menunggu|dikonfirmasi|terlewat

            $table->uuid('konfirmasi_log_id')->nullable();
            $table->dateTime('dikonfirmasi_pada')->nullable();

            $table->unsignedInteger('jumlah_push')->default(0);
            $table->unsignedInteger('jumlah_wa_pasien')->default(0);
            $table->unsignedInteger('jumlah_wa_pmo')->default(0);
            $table->dateTime('terakhir_dikirim_pada')->nullable();
            $table->boolean('eskalasi_pmo')->default(false);

            $table->timestamps();

            $table->unique(['jenis', 'jadwal_id', 'waktu_jadwal'], 'uq_kejadian_slot');
            $table->index(['status', 'waktu_jadwal'], 'idx_status_waktu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengingat_kejadian');
    }
};
