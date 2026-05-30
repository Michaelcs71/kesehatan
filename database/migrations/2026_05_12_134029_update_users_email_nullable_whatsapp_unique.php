<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 1. Email jadi nullable (untuk masa depan)
            $table->string('email', 255)->nullable()->change();

            // 2. WhatsApp number jadi unique constraint
            // (sudah ada column-nya, tinggal tambah unique index)
            $table->unique('whatsapp_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['whatsapp_number']);
            $table->string('email', 255)->nullable(false)->change();
        });
    }
};
