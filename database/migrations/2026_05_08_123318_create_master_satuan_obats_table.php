<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_satuan_obats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama', 50);
            $table->string('singkatan', 20)->nullable();
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);

            // Audit
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('nama');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_satuan_obats');
    }
};
