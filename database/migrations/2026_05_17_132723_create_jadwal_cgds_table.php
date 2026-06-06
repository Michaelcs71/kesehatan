<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_cgds', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Schedule info
            $table->date('tgl_input');                              // tanggal input ke sistem
            $table->date('tgl_jadwal_cgd');                         // tanggal pelaksanaan event
            $table->time('jam_mulai');
            $table->time('jam_berakhir');
            $table->string('puasa', 10);                            // 'Wajib' | 'Tidak'
            $table->string('tempat', 255);                          // e.g. "Posyandu Lebakharjo"
            $table->text('catatan')->nullable();                    // catatan tambahan opsional
            $table->string('status', 20)->default('aktif');         // 'aktif' | 'nonaktif' | 'selesai'

            // Audit
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tgl_jadwal_cgd');
            $table->index('status');
            $table->index(['status', 'tgl_jadwal_cgd'], 'idx_status_tgl');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_cgds');
    }
};
