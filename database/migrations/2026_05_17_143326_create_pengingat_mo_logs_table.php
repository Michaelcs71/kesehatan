<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengingat_mo_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // FK
            $table->foreignUuid('id_jo')
                ->constrained('jadwal_minum_obats')->cascadeOnDelete();
            $table->foreignUuid('id_user')
                ->nullable()->constrained('users')->nullOnDelete();

            // Snapshot info untuk display cepat (dari jadwal saat input)
            $table->string('nama_pasien', 100)->nullable();
            $table->string('nama_obat', 100)->nullable();

            // Waktu
            $table->date('tgl_minum_obat');
            $table->time('jam_minum_obat');
            $table->time('jam_slot_target')->nullable();  // slot yang seharusnya (e.g. 08:00)

            // Patuh (selisih menit, negatif = lebih awal, positif = telat)
            $table->integer('patuh_menit')->default(0);

            // Foto (wajib)
            $table->string('foto_obat', 500);

            // Status
            $table->string('status', 20)->default('aktif');  // aktif | nonaktif

            // Audit
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['id_jo', 'tgl_minum_obat'], 'idx_jo_tgl');
            $table->index(['id_user', 'tgl_minum_obat'], 'idx_user_tgl');
            $table->index('tgl_minum_obat');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengingat_mo_logs');
    }
};
