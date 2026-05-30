<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\PasienProfile;
use App\Models\PmoProfile;
use App\Models\User;
use App\Models\UserBiodata;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            // 1. Buat user
            $user = User::create([
                'name'            => $request->name,
                'username'        => $this->generateUsername($request->name),
                'password'        => Hash::make($request->password),
                'role'            => $request->role,
                'whatsapp_number' => $request->whatsapp_number,
                'is_active'       => true,
            ]);

            // 2. Buat profile sesuai role
            if ($request->role === UserRole::PASIEN->value) {
                PasienProfile::create(['user_id' => $user->id]);
            } elseif ($request->role === UserRole::PMO->value) {
                PmoProfile::create(['user_id' => $user->id]);
            }

            // 3. Buat biodata
            UserBiodata::create([
                'user_id'          => $user->id,
                'nik'              => $request->nik,
                'no_kk'            => $request->no_kk,
                'jenis_kelamin'    => $request->jenis_kelamin,
                'tempat_lahir'     => $request->tempat_lahir,
                'tanggal_lahir'    => $request->tanggal_lahir,
                'alamat_jalan'     => $request->alamat_jalan,
                'alamat_rt'        => $request->alamat_rt,
                'alamat_rw'        => $request->alamat_rw,
                'alamat_dusun'     => $request->alamat_dusun,
                'alamat_desa'      => $request->alamat_desa,
                'alamat_kecamatan' => $request->alamat_kecamatan,
                'alamat_kabupaten' => $request->alamat_kabupaten,
                'alamat_provinsi'  => $request->alamat_provinsi,
                'alamat_kodepos'   => $request->alamat_kodepos,
            ]);

            // 4. Sync Spatie role
            $user->syncRoles([$request->role]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route($user->homeRoute());
    }

    /**
     * Generate username unique dari nama lengkap.
     * "Ahmad Fauzi" -> "ahmad.fauzi" (atau ahmad.fauzi1, ahmad.fauzi2 kalau sudah ada)
     */
    protected function generateUsername(string $name): string
    {
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '.', trim($name)));
        $base = trim($base, '.');

        $username = $base;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }
}
