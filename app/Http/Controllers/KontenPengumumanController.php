<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudOperations;
use App\Http\Controllers\Concerns\HasCrudViews;
use App\Http\Requests\Pengumuman\IndexRequest;
use App\Http\Requests\Pengumuman\StoreUpdateRequest;
use App\Services\PengumumanService;
use Illuminate\Http\JsonResponse;

class KontenPengumumanController extends BaseController
{
    use HasCrudOperations, HasCrudViews;

    protected function getViewPath(): string
    {
        return 'konten-pengumuman';
    }

    protected function getRouteName(): string
    {
        return 'konten-pengumuman';
    }

    protected function getService(): string
    {
        return PengumumanService::class;
    }

    protected function getEntityName(): string
    {
        return 'pengumuman';
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
        try {
            $item = $this->executeTransaction(function () use ($request) {
                return PengumumanService::createPengumuman($request->validated(), $request);
            });

            return $this->successResponse($this->getSuccessMessage('created'), $item);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function show(string $id)
    {
        if (! PengumumanService::findPengumumanById($id)) {
            return $this->redirectToIndexWithError();
        }

        return $this->showView($id);
    }

    public function showData(string $id): JsonResponse
    {
        $item = PengumumanService::findPengumumanById($id);
        if (! $item) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        return response()->json($item);
    }

    public function edit(string $id)
    {
        return $this->editView($id);
    }

    public function update(StoreUpdateRequest $request, string $id): JsonResponse
    {
        try {
            $result = $this->executeTransaction(function () use ($request, $id) {
                return PengumumanService::updatePengumuman($id, $request->validated(), $request);
            });

            if (! $result) {
                return $this->errorResponse('Data tidak ditemukan atau gagal diupdate', 404);
            }

            return $this->successResponse($this->getSuccessMessage('updated'));
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        return $this->handleDestroy($id);
    }
}
