<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengingat_kirim_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kejadian_id');
            $table->string('kanal', 15);   // push | whatsapp
            $table->string('target', 10);  // pasien | pmo
            $table->string('status', 12);  // terkirim | gagal
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('kejadian_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengingat_kirim_log');
    }
};
