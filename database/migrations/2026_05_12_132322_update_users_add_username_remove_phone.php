<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah column username (nullable dulu, biar bisa migrasi data)
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 100)->nullable()->after('name');
        });

        // 2. Migrasi data: isi username dari email (bagian sebelum @)
        // Untuk user existing, username = bagian sebelum '@' di email
        $users = DB::table('users')->whereNull('username')->get();
        foreach ($users as $user) {
            $username = explode('@', $user->email)[0] ?? null;
            if ($username) {
                // Pastikan unique - tambah counter kalau ada duplikat
                $baseUsername = $username;
                $counter = 1;
                while (DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                    $username = $baseUsername . $counter;
                    $counter++;
                }
                DB::table('users')->where('id', $user->id)->update(['username' => $username]);
            }
        }

        // 3. Set NOT NULL + unique constraint
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 100)->nullable(false)->change();
            $table->unique('username');
        });

        // 4. Hapus column phone
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
            $table->string('phone', 20)->nullable()->after('email');
        });
    }
};
