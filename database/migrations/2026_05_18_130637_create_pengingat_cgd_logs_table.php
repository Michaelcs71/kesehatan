<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengingat_cgd_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // FK
            $table->foreignUuid('id_cgd')
                ->constrained('jadwal_cgds')->cascadeOnDelete();
            $table->foreignUuid('id_user')
                ->nullable()->constrained('users')->nullOnDelete();

            // Snapshot info
            $table->string('nama_pasien', 100)->nullable();
            $table->string('jenis_kelamin', 5)->nullable();
            $table->string('tempat_cgd', 255)->nullable();

            // Waktu & hasil
            $table->date('tgl_cgd');
            $table->time('jam_cgd');
            $table->integer('hasil_mgdl');

            // Klasifikasi
            $table->string('kategori_hasil', 30);
            $table->integer('patuh_selisih')->default(0);

            // Foto wajib
            $table->string('foto_layar', 500);

            // Status
            $table->string('status', 20)->default('aktif');

            // Audit
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            // Indexes
            $table->index(['id_cgd', 'tgl_cgd'], 'idx_cgd_log_cgd_tgl');
            $table->index(['id_user', 'tgl_cgd'], 'idx_cgd_log_user_tgl');
            $table->index('kategori_hasil', 'idx_cgd_log_kategori');
            $table->index('status', 'idx_cgd_log_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengingat_cgd_logs');
    }
};
