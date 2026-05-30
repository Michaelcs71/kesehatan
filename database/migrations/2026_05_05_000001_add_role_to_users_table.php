<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('pasien')->after('email');
            $table->string('phone', 20)->nullable()->after('role');
            $table->string('whatsapp_number', 20)->nullable()->after('phone');
            $table->string('avatar_path')->nullable()->after('whatsapp_number');
            $table->boolean('is_active')->default(true)->after('avatar_path');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'phone', 'whatsapp_number', 'avatar_path', 'is_active']);
        });
    }
};
