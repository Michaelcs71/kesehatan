<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edukasi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('judul', 200);
            $table->string('slug', 220)->unique();
            $table->string('kategori', 100)->nullable();
            $table->string('ringkasan', 500)->nullable();
            $table->longText('konten');
            $table->string('gambar_path')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();

            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('is_published');
            $table->index('kategori');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edukasi');
    }
};
