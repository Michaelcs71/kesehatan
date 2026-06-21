<?php

namespace App\Http\Controllers;

use App\Services\ImpersonationService;
use Illuminate\Http\RedirectResponse;

class ImpersonationController extends Controller
{
    public function mulai(string $role): RedirectResponse
    {
        $user = auth()->user();

        // Belt-and-suspenders: route sudah digate role:superadmin.
        if (! $user || ! $user->isSuperadmin() || ImpersonationService::sedangImpersonate()) {
            abort(403);
        }

        try {
            $target = ImpersonationService::mulai($role);
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

        ImpersonationService::kembali();

        return redirect()->route(auth()->user()->homeRoute())
            ->with('success', 'Kembali ke akun Super Admin.');
    }
}
