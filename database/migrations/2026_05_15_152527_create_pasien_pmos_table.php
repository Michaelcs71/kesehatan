<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pasien_pmos', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // FK ke user pasien (sesuai gambar: id_user)
            $table->foreignUuid('id_user')->nullable()
                ->constrained('users')->nullOnDelete();

            // FK ke user PMO (untuk query "pasien-pasien milik PMO X")
            $table->foreignUuid('pmo_user_id')->nullable()
                ->constrained('users')->nullOnDelete();

            // Snapshot data (sesuai gambar)
            $table->string('nama_pasien', 100);
            $table->string('nik', 16);
            $table->string('nama_pmo', 100);
            $table->string('jenis_pmo', 20);          // 'Keluarga' / 'Kader'
            $table->date('tanggal_regis');
            $table->string('status_diabetes', 20);    // 'Rendah' / 'Sedang' / 'Tinggi'

            // Tambahan untuk control & history
            $table->boolean('is_active')->default(true);
            $table->text('catatan')->nullable();

            // Audit
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('id_user');
            $table->index('pmo_user_id');
            $table->index('is_active');
            $table->index(['id_user', 'is_active'], 'idx_pasien_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pasien_pmos');
    }
};
