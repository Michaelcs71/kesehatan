<?php

namespace App\Http\Controllers;

use App\Services\ImpersonationService;
use Illuminate\Http\RedirectResponse;

class ImpersonationController extends Controller
{
    public function mulai(string $role): RedirectResponse
    {
        // Operator = superadmin asli: saat ber-POV ambil dari session, selain itu current user.
        $operator = ImpersonationService::sedangImpersonate()
            ? ImpersonationService::impersonator()
            : auth()->user();

        if (! $operator || ! $operator->isSuperadmin()) {
            abort(403);
        }

        try {
            $target = ImpersonationService::mulaiSebagai($operator, $role);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route($target->homeRoute())
            ->with('success', 'Sekarang melihat sebagai '.$target->name.' ('.$target->role->label().').');
    }

    public function kembali(): RedirectResponse
    {
        if (! ImpersonationService::sedangImpersonate()) {
            return redirect()->route('dashboard');
        }

        if (! ImpersonationService::kembali()) {
            auth()->logout();

            return redirect()->route('login')
                ->with('error', 'Akun Super Admin asal tidak ditemukan. Silakan login ulang.');
        }

        return redirect()->route(auth()->user()->homeRoute())
            ->with('success', 'Kembali ke akun Super Admin.');
    }
}
