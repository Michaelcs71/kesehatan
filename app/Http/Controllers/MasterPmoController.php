<?php

namespace App\Http\Controllers;

use App\Services\MasterDirektoriService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterPmoController extends Controller
{
    public function index(Request $request): View
    {
        return view('master-pmo.index', [
            'daftar' => MasterDirektoriService::daftarPmo(['cari' => $request->query('cari')]),
            'cari' => $request->query('cari'),
        ]);
    }

    public function show(string $id): View
    {
        $detail = MasterDirektoriService::detailPmo($id);
        abort_if($detail === null, 404);

        return view('master-pmo.show', ['d' => $detail]);
    }
}
