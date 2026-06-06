<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasienPmo\IndexRequest;
use App\Http\Requests\PasienPmo\StoreRequest;
use App\Http\Requests\PasienPmo\UpdateRequest;
use App\Services\PasienPmoService;
use Illuminate\Http\JsonResponse;

class PasienPmoController extends Controller
{
    protected function getViewPath(): string
    {
        return 'pasien-pmo';
    }

    protected function getRouteName(): string
    {
        return 'pasien-pmo';
    }

    public function index()
    {
        return view($this->getViewPath().'.index');
    }

    public function getData(IndexRequest $request): JsonResponse
    {
        $data = PasienPmoService::getAllMappings($request->validated());

        return response()->json($data);
    }

    public function create()
    {
        return view($this->getViewPath().'.form');
    }

    public function store(StoreRequest $request): JsonResponse
    {
        try {
            $result = PasienPmoService::bulkCreate($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Berhasil membuat {$result['count']} mapping Pasien PMO.",
                'data' => $result,
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
        $mapping = PasienPmoService::findMappingById($id);

        if (! $mapping) {
            return redirect()->route('pasien-pmo.index')
                ->with('error', 'Mapping tidak ditemukan.');
        }

        return view($this->getViewPath().'.show', compact('id'));
    }

    public function showData(string $id): JsonResponse
    {
        $mapping = PasienPmoService::findMappingById($id);

        if (! $mapping) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($mapping);
    }

    public function edit(string $id)
    {
        $mapping = PasienPmoService::findMappingById($id);

        if (! $mapping) {
            return redirect()->route('pasien-pmo.index')
                ->with('error', 'Mapping tidak ditemukan.');
        }

        return view($this->getViewPath().'.form', compact('id'));
    }

    public function update(UpdateRequest $request, string $id): JsonResponse
    {
        try {
            PasienPmoService::updateMapping($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Mapping berhasil diupdate.',
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
            PasienPmoService::deleteMapping($id);

            return response()->json([
                'success' => true,
                'message' => 'Mapping berhasil dihapus.',
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
            PasienPmoService::deactivate($id);

            return response()->json([
                'success' => true,
                'message' => 'Mapping berhasil dinonaktifkan.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function activate(string $id): JsonResponse
    {
        try {
            PasienPmoService::activate($id);

            return response()->json([
                'success' => true,
                'message' => 'Mapping berhasil diaktifkan.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ===== Options endpoints =====

    public function pmoOptions(): JsonResponse
    {
        return response()->json([
            'data' => PasienPmoService::getPmoOptions(),
        ]);
    }

    public function pasienOptions(): JsonResponse
    {
        $excludeMappingId = request('exclude_mapping_id');

        return response()->json([
            'data' => PasienPmoService::getPasienOptions($excludeMappingId),
        ]);
    }
}
