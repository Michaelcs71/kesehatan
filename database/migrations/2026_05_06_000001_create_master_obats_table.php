<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_obats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama', 200);
            $table->enum('kategori', ['oral', 'injeksi', 'insulin', 'lainnya'])->default('oral');
            $table->string('dosis_default', 50);
            $table->enum('satuan', ['tablet', 'kapsul', 'ml', 'mg', 'IU', 'sachet'])->default('tablet');
            $table->text('deskripsi')->nullable();
            $table->text('aturan_minum')->nullable();
            $table->text('efek_samping')->nullable();
            $table->text('kontraindikasi')->nullable();
            $table->string('foto_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('created_by');
            $table->index('nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_obats');
    }
};