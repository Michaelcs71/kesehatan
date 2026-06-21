<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_pengingat', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Minum Obat
            $table->boolean('mo_aktif')->default(true);
            $table->unsignedSmallInteger('mo_jumlah')->default(4);            // N
            $table->unsignedSmallInteger('mo_interval_menit')->default(15);   // X
            $table->unsignedSmallInteger('mo_pmo_mulai_ke')->default(3);      // M

            // Cek Gula Darah
            $table->boolean('cgd_aktif')->default(true);
            $table->boolean('cgd_dibuat_aktif')->default(true);
            $table->string('cgd_jam_h1', 5)->default('17:00');               // 'HH:MM'

            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_pengingat');
    }
};
