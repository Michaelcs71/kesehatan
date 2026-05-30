<?php

namespace App\Http\Controllers;

use App\Http\Requests\PengingatCgdLog\{IndexRequest, StoreRequest, UpdateRequest};
use App\Services\PengingatCgdLogService;
use Illuminate\Http\JsonResponse;

class PengingatCgdLogController extends Controller
{
    protected function getViewPath(): string
    {
        return 'pengingat-cgd';
    }

    public function index()
    {
        return view($this->getViewPath() . '.index');
    }

    public function getData(IndexRequest $request): JsonResponse
    {
        $data = PengingatCgdLogService::getAllLogs($request->validated());
        return response()->json($data);
    }

    public function create()
    {
        return view($this->getViewPath() . '.form');
    }

    public function store(StoreRequest $request): JsonResponse
    {
        try {
            $log = PengingatCgdLogService::createLog(
                $request->safe()->except('foto_layar'),
                $request->file('foto_layar')
            );

            return response()->json([
                'success' => true,
                'message' => 'Hasil cek gula darah berhasil disimpan.',
                'data'    => $log,
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
        $log = PengingatCgdLogService::findLogById($id);

        if (!$log) {
            return redirect()->route('pengingat-cgd.index')
                ->with('error', 'Log tidak ditemukan.');
        }

        return view($this->getViewPath() . '.show', compact('id'));
    }

    public function showData(string $id): JsonResponse
    {
        $log = PengingatCgdLogService::findLogById($id);

        if (!$log) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($log);
    }

    public function edit(string $id)
    {
        $log = PengingatCgdLogService::findLogById($id);

        if (!$log) {
            return redirect()->route('pengingat-cgd.index')
                ->with('error', 'Log tidak ditemukan.');
        }

        return view($this->getViewPath() . '.form', compact('id'));
    }

    public function update(UpdateRequest $request, string $id): JsonResponse
    {
        try {
            PengingatCgdLogService::updateLog(
                $id,
                $request->safe()->except('foto_layar'),
                $request->file('foto_layar')
            );

            return response()->json([
                'success' => true,
                'message' => 'Log berhasil diupdate.',
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
            PengingatCgdLogService::deleteLog($id);
            return response()->json([
                'success' => true,
                'message' => 'Log berhasil dihapus.',
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
            PengingatCgdLogService::deactivate($id);
            return response()->json(['success' => true, 'message' => 'Log dinonaktifkan.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function activate(string $id): JsonResponse
    {
        try {
            PengingatCgdLogService::activate($id);
            return response()->json(['success' => true, 'message' => 'Log diaktifkan.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ===== Options =====

    public function jadwalCgdOptions(): JsonResponse
    {
        return response()->json(['data' => PengingatCgdLogService::getJadwalCgdOptions()]);
    }
}
