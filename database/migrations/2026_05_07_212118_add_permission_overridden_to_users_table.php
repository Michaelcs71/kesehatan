<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Flag: true = user pakai direct permissions saja (skip role inheritance),
            //       false = pakai role default + direct permissions (Spatie default)
            $table->boolean('permission_overridden')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('permission_overridden');
        });
    }
};
