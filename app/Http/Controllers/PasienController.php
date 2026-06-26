<?php

namespace App\Http\Controllers;

use App\Services\PasienRiwayatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PasienController extends Controller
{
    public function jadwalCgd(): View
    {
        return view('pasien.jadwal-cgd', PasienRiwayatService::jadwalCgdPasien(Auth::id()));
    }

    public function riwayat(Request $request): View
    {
        $tab = in_array($request->query('tab'), ['obat', 'gula'], true)
            ? $request->query('tab') : 'obat';

        $filter = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
        ]);

        $userId = Auth::id();

        return view('pasien.riwayat', [
            'tab' => $tab,
            'pending' => PasienRiwayatService::pendingKonfirmasi($userId),
            'riwayatMo' => $tab === 'obat' ? PasienRiwayatService::riwayatMo($userId, $filter) : null,
            'riwayatCgd' => $tab === 'gula' ? PasienRiwayatService::riwayatCgd($userId, $filter) : null,
        ]);
    }

    public function pengingatMo(): RedirectResponse
    {
        return redirect()->route('pasien.riwayat', ['tab' => 'obat']);
    }

    public function pengingatCgd(): RedirectResponse
    {
        return redirect()->route('pasien.riwayat', ['tab' => 'gula']);
    }
}
