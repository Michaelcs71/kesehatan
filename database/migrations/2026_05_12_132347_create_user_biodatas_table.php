<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_biodatas', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Foreign key ke user (1-to-1)
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();

            // Identitas
            $table->string('nik', 16)->nullable()->unique();
            $table->string('no_kk', 16)->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('tempat_lahir', 50)->nullable();
            $table->date('tanggal_lahir')->nullable();

            // Alamat
            $table->string('alamat_jalan', 255)->nullable();
            $table->string('alamat_rt', 5)->nullable();
            $table->string('alamat_rw', 5)->nullable();
            $table->string('alamat_dusun', 100)->nullable();
            $table->string('alamat_desa', 100)->nullable();
            $table->string('alamat_kecamatan', 100)->nullable();
            $table->string('alamat_kabupaten', 100)->nullable();
            $table->string('alamat_provinsi', 100)->nullable();
            $table->string('alamat_kodepos', 10)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('nik');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_biodatas');
    }
};
