<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\{HasCrudViews, HasCrudOperations};
use App\Http\Requests\MasterKategoriObat\{IndexRequest, StoreUpdateRequest};
use App\Services\MasterKategoriObatService;
use Illuminate\Http\JsonResponse;

class MasterKategoriObatController extends BaseController
{
    use HasCrudViews, HasCrudOperations;

    protected function getViewPath(): string
    {
        return 'master-kategori-obat';
    }

    protected function getRouteName(): string
    {
        return 'master-kategori-obat';
    }

    protected function getService(): string
    {
        return MasterKategoriObatService::class;
    }

    protected function getEntityName(): string
    {
        return 'master kategori obat';
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
        $item = MasterKategoriObatService::findMasterKategoriObatById($id);

        if (!$item) {
            return $this->redirectToIndexWithError();
        }

        return $this->showView($id);
    }

    public function showData(string $id): JsonResponse
    {
        $item = MasterKategoriObatService::findMasterKategoriObatById($id);

        if (!$item) {
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

    /**
     * Endpoint untuk dropdown (active kategori only)
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'data' => MasterKategoriObatService::getActiveOptions(),
        ]);
    }
}