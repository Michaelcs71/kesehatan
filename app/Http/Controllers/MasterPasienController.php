<?php

namespace App\Http\Controllers;

use App\Services\MasterDirektoriService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterPasienController extends Controller
{
    public function index(Request $request): View
    {
        return view('master-pasien.index', [
            'daftar' => MasterDirektoriService::daftarPasien(['cari' => $request->query('cari')]),
            'cari' => $request->query('cari'),
        ]);
    }

    public function show(string $id): View
    {
        $detail = MasterDirektoriService::detailPasien($id);
        abort_if($detail === null, 404);

        return view('master-pasien.show', ['d' => $detail]);
    }
}
