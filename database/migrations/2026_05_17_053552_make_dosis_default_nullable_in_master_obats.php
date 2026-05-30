<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_obats', function (Blueprint $table) {
            $table->string('dosis_default')->nullable()->change();
        });
    }

    public function down(): void
    {
        // PERHATIAN: rollback akan gagal kalau ada row dengan dosis_default = null
        Schema::table('master_obats', function (Blueprint $table) {
            $table->string('dosis_default')->nullable(false)->change();
        });
    }
};
