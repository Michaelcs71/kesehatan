<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudOperations;
use App\Http\Controllers\Concerns\HasCrudViews;
use App\Http\Requests\MasterObat\IndexRequest;
use App\Http\Requests\MasterObat\StoreUpdateRequest;
use App\Http\Requests\MasterObat\VerifikasiRequest;
use App\Services\MasterObatService;
use Illuminate\Http\JsonResponse;

/**
 * Controller for Master Obat CRUD + Verifikasi
 */
class MasterObatController extends BaseController
{
    use HasCrudOperations, HasCrudViews;

    protected function getViewPath(): string
    {
        return 'master-obat';
    }

    protected function getRouteName(): string
    {
        return 'master-obat';
    }

    protected function getService(): string
    {
        return MasterObatService::class;
    }

    protected function getEntityName(): string
    {
        return 'obat';
    }

    /**
     * Display listing
     */
    public function index()
    {
        return $this->indexView();
    }

    /**
     * Get data for DataTable (AJAX)
     */
    public function getData(IndexRequest $request)
    {
        return $this->handleGetData($request);
    }

    /**
     * Show create form
     */
    public function create()
    {
        return $this->createView();
    }

    /**
     * Store new obat
     */
    public function store(StoreUpdateRequest $request): JsonResponse
    {
        try {
            $obat = $this->executeTransaction(function () use ($request) {
                return MasterObatService::createObat($request->validated(), $request);
            });

            return $this->successResponse($this->getSuccessMessage('created'), $obat);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Show detail page
     */
    public function show(string $id)
    {
        $obat = MasterObatService::findObatById($id);

        if (! $obat) {
            return $this->redirectToIndexWithError();
        }

        return $this->showView($id);
    }

    /**
     * Get detail data as JSON
     */
    public function showData(string $id): JsonResponse
    {
        $obat = MasterObatService::findObatById($id);

        if (! $obat) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        return response()->json($obat);
    }

    /**
     * Show edit form
     */
    public function edit(string $id)
    {
        return $this->editView($id);
    }

    /**
     * Update obat
     */
    public function update(StoreUpdateRequest $request, string $id): JsonResponse
    {
        try {
            $this->executeTransaction(function () use ($request, $id) {
                MasterObatService::updateObat($id, $request->validated(), $request);
            });

            return $this->successResponse($this->getSuccessMessage('updated'));
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Delete obat (soft delete)
     */
    public function destroy(string $id): JsonResponse
    {
        return $this->handleDestroy($id);
    }

    /**
     * Verify obat (approve/reject) - admin only
     */
    public function verify(VerifikasiRequest $request, string $id): JsonResponse
    {
        try {
            $this->executeTransaction(function () use ($request, $id) {
                MasterObatService::verifyObat($id, $request->validated());
            });

            $msg = $request->status === 'approved'
                ? 'Obat berhasil di-approve'
                : 'Obat di-reject. Pengaju akan mendapat notifikasi.';

            return $this->successResponse($msg);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
