<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudOperations;
use App\Http\Controllers\Concerns\HasCrudViews;
use App\Http\Requests\MasterSatuanObat\IndexRequest;
use App\Http\Requests\MasterSatuanObat\StoreUpdateRequest;
use App\Services\MasterSatuanObatService;
use Illuminate\Http\JsonResponse;

class MasterSatuanObatController extends BaseController
{
    use HasCrudOperations, HasCrudViews;

    protected function getViewPath(): string
    {
        return 'master-satuan-obat';
    }

    protected function getRouteName(): string
    {
        return 'master-satuan-obat';
    }

    protected function getService(): string
    {
        return MasterSatuanObatService::class;
    }

    protected function getEntityName(): string
    {
        return 'master satuan obat';
    }

    public function index()
    {
        return $this->indexView();
    }

    public function getData(IndexRequest $request)
    {
        return $this->handleGetData($request);
    }

    public function create()
    {
        return $this->createView();
    }

    public function store(StoreUpdateRequest $request): JsonResponse
    {
        return $this->handleStore($request);
    }

    public function show(string $id)
    {
        $item = MasterSatuanObatService::findMasterSatuanObatById($id);

        if (! $item) {
            return $this->redirectToIndexWithError();
        }

        return $this->showView($id);
    }

    public function showData(string $id): JsonResponse
    {
        $item = MasterSatuanObatService::findMasterSatuanObatById($id);

        if (! $item) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($item);
    }

    public function edit(string $id)
    {
        return $this->editView($id);
    }

    public function update(StoreUpdateRequest $request, string $id): JsonResponse
    {
        return $this->handleUpdate($request, $id);
    }

    public function destroy(string $id): JsonResponse
    {
        return $this->handleDestroy($id);
    }

    public function options(): JsonResponse
    {
        return response()->json([
            'data' => MasterSatuanObatService::getActiveOptions(),
        ]);
    }
}
