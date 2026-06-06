<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pasien_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('pmo_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nik', 20)->nullable()->unique();
            $table->string('no_bpjs', 20)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->text('alamat')->nullable();
            $table->string('kota', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->enum('tipe_diabetes', ['tipe_1', 'tipe_2', 'gestasional', 'lainnya'])->nullable();
            $table->date('tanggal_diagnosis')->nullable();
            $table->decimal('tinggi_badan', 5, 2)->nullable()->comment('cm');
            $table->decimal('berat_badan', 5, 2)->nullable()->comment('kg');
            $table->text('catatan_medis')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('pmo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pasien_profiles');
    }
};
