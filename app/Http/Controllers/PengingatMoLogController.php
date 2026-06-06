<?php

namespace App\Http\Controllers;

use App\Http\Requests\PengingatMoLog\IndexRequest;
use App\Http\Requests\PengingatMoLog\StoreRequest;
use App\Http\Requests\PengingatMoLog\UpdateRequest;
use App\Services\PengingatMoLogService;
use Illuminate\Http\JsonResponse;

class PengingatMoLogController extends Controller
{
    protected function getViewPath(): string
    {
        return 'pengingat-mo';
    }

    public function index()
    {
        return view($this->getViewPath().'.index');
    }

    public function getData(IndexRequest $request): JsonResponse
    {
        $data = PengingatMoLogService::getAllLogs($request->validated());

        return response()->json($data);
    }

    public function create()
    {
        return view($this->getViewPath().'.form');
    }

    public function store(StoreRequest $request): JsonResponse
    {
        try {
            $log = PengingatMoLogService::createLog(
                $request->safe()->except('foto_obat'),
                $request->file('foto_obat')
            );

            return response()->json([
                'success' => true,
                'message' => 'Konfirmasi minum obat berhasil disimpan.',
                'data' => $log,
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
        $log = PengingatMoLogService::findLogById($id);

        if (! $log) {
            return redirect()->route('pengingat-mo.index')
                ->with('error', 'Log tidak ditemukan.');
        }

        return view($this->getViewPath().'.show', compact('id'));
    }

    public function showData(string $id): JsonResponse
    {
        $log = PengingatMoLogService::findLogById($id);

        if (! $log) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($log);
    }

    public function edit(string $id)
    {
        $log = PengingatMoLogService::findLogById($id);

        if (! $log) {
            return redirect()->route('pengingat-mo.index')
                ->with('error', 'Log tidak ditemukan.');
        }

        return view($this->getViewPath().'.form', compact('id'));
    }

    public function update(UpdateRequest $request, string $id): JsonResponse
    {
        try {
            PengingatMoLogService::updateLog(
                $id,
                $request->safe()->except('foto_obat'),
                $request->file('foto_obat')
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
            PengingatMoLogService::deleteLog($id);

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
            PengingatMoLogService::deactivate($id);

            return response()->json(['success' => true, 'message' => 'Log dinonaktifkan.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function activate(string $id): JsonResponse
    {
        try {
            PengingatMoLogService::activate($id);

            return response()->json(['success' => true, 'message' => 'Log diaktifkan.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ===== Options for form dropdown =====

    public function jadwalOptions(): JsonResponse
    {
        return response()->json(['data' => PengingatMoLogService::getJadwalOptions()]);
    }
}
