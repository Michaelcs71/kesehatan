<?php

namespace App\Http\Controllers;

use App\Http\Requests\JadwalCgd\IndexRequest;
use App\Http\Requests\JadwalCgd\StoreRequest;
use App\Http\Requests\JadwalCgd\UpdateRequest;
use App\Services\JadwalCgdService;
use Illuminate\Http\JsonResponse;

class JadwalCgdController extends Controller
{
    protected function getViewPath(): string
    {
        return 'jadwal-cgd';
    }

    public function index()
    {
        return view($this->getViewPath().'.index');
    }

    public function getData(IndexRequest $request): JsonResponse
    {
        $data = JadwalCgdService::getAllJadwal($request->validated());

        return response()->json($data);
    }

    public function create()
    {
        return view($this->getViewPath().'.form');
    }

    public function store(StoreRequest $request): JsonResponse
    {
        try {
            $jadwal = JadwalCgdService::createJadwal($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Jadwal CGD berhasil dibuat.',
                'data' => $jadwal,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(string $id)
    {
        $jadwal = JadwalCgdService::findJadwalById($id);

        if (! $jadwal) {
            return redirect()->route('jadwal-cgd.index')
                ->with('error', 'Jadwal CGD tidak ditemukan.');
        }

        return view($this->getViewPath().'.show', compact('id'));
    }

    public function showData(string $id): JsonResponse
    {
        $jadwal = JadwalCgdService::findJadwalById($id);

        if (! $jadwal) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($jadwal);
    }

    public function edit(string $id)
    {
        $jadwal = JadwalCgdService::findJadwalById($id);

        if (! $jadwal) {
            return redirect()->route('jadwal-cgd.index')
                ->with('error', 'Jadwal CGD tidak ditemukan.');
        }

        return view($this->getViewPath().'.form', compact('id'));
    }

    public function update(UpdateRequest $request, string $id): JsonResponse
    {
        try {
            JadwalCgdService::updateJadwal($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Jadwal CGD berhasil diupdate.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            JadwalCgdService::deleteJadwal($id);

            return response()->json([
                'success' => true,
                'message' => 'Jadwal CGD berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function deactivate(string $id): JsonResponse
    {
        try {
            JadwalCgdService::deactivate($id);

            return response()->json(['success' => true, 'message' => 'Jadwal dinonaktifkan.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function activate(string $id): JsonResponse
    {
        try {
            JadwalCgdService::activate($id);

            return response()->json(['success' => true, 'message' => 'Jadwal diaktifkan.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function markSelesai(string $id): JsonResponse
    {
        try {
            JadwalCgdService::markSelesai($id);

            return response()->json(['success' => true, 'message' => 'Jadwal ditandai selesai.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
