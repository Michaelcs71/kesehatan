<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_minum_obats', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // FK
            $table->foreignUuid('id_pasien_pmo')
                ->constrained('pasien_pmos')->cascadeOnDelete();
            $table->foreignUuid('obat_id')
                ->constrained('master_obats')->restrictOnDelete();

            // Snapshot (sesuai gambar)
            $table->string('nama_pasien', 100);
            $table->string('nama_pmo', 100);

            // Schedule master
            $table->date('tgl_mulai');
            $table->time('jam_mulai');
            $table->integer('frekuensi_per_hari');           // 1, 2, 3
            $table->integer('durasi_hari')->nullable();      // null = unlimited
            $table->text('catatan_dosis')->nullable();
            $table->string('status', 20)->default('aktif');  // aktif | nonaktif | selesai

            // Audit
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('id_pasien_pmo');
            $table->index('obat_id');
            $table->index('status');
            $table->index(['id_pasien_pmo', 'status'], 'idx_pasien_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_minum_obats');
    }
};
