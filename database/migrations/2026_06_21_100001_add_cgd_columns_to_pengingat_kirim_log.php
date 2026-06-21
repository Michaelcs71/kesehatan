<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengingat_kirim_log', function (Blueprint $table) {
            $table->uuid('kejadian_id')->nullable()->change();
            $table->uuid('peserta_id')->nullable()->after('kejadian_id');
            $table->string('fase', 10)->nullable()->after('target'); // 'dibuat' | 'h1'
            $table->index('peserta_id');
        });
    }

    public function down(): void
    {
        Schema::table('pengingat_kirim_log', function (Blueprint $table) {
            $table->dropIndex(['peserta_id']);
            $table->dropColumn(['peserta_id', 'fase']);
            $table->uuid('kejadian_id')->nullable(false)->change();
        });
    }
};
