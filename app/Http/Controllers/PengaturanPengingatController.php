<?php

namespace App\Http\Controllers;

use App\Http\Requests\PengaturanPengingat\UpdateRequest;
use App\Services\PengaturanPengingatService;
use Illuminate\Http\JsonResponse;

class PengaturanPengingatController extends BaseController
{
    public function index()
    {
        $pengaturan = PengaturanPengingatService::get();

        return view('pengaturan-pengingat.index', compact('pengaturan'));
    }

    public function update(UpdateRequest $request): JsonResponse
    {
        try {
            PengaturanPengingatService::update($request->validated());

            return $this->successResponse('Pengaturan pengingat berhasil disimpan.');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Gagal menyimpan pengaturan.');
        }
    }
}
