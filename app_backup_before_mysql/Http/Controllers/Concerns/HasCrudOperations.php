<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

trait HasCrudOperations
{
    /**
     * Get the service class name (full namespace)
     */
    abstract protected function getService(): string;

    /**
     * Get entity name for messages (e.g. "obat", "pasien")
     */
    protected function getEntityName(): string
    {
        return 'data';
    }

    /**
     * Handle get data for DataTable/Grid (server-side pagination)
     * Returns: ['TotalRows' => N, 'Rows' => [...]] for jqxGrid compat
     * Or array compatible with DataTables.net
     */
    protected function handleGetData($request): array
    {
        $params  = $request->validated();
        $service = $this->getService();

        return $service::getAll($params);
    }

    /**
     * Handle show data as JSON (for AJAX detail load)
     */
    protected function handleShowData(string $id): JsonResponse
    {
        $service = $this->getService();
        $data    = $service::getById($id);

        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        return response()->json($data);
    }

    /**
     * Handle store operation
     */
    protected function handleStore($request): JsonResponse
    {
        try {
            $data = $this->executeTransaction(function () use ($request) {
                $service = $this->getService();
                return $service::create($request->validated());
            });

            return $this->successResponse($this->getSuccessMessage('created'), $data);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Handle update operation
     */
    protected function handleUpdate($request, string $id): JsonResponse
    {
        try {
            $result = $this->executeTransaction(function () use ($request, $id) {
                $service = $this->getService();
                return $service::update($id, $request->validated());
            });

            if (!$result) {
                return $this->errorResponse('Data tidak ditemukan atau gagal diupdate', 404);
            }

            return $this->successResponse($this->getSuccessMessage('updated'));
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Handle destroy operation
     */
    protected function handleDestroy(string $id): JsonResponse
    {
        try {
            $service = $this->getService();
            $result  = $service::delete($id);

            if (!$result) {
                return $this->errorResponse('Data tidak ditemukan', 404);
            }

            return $this->successResponse($this->getSuccessMessage('deleted'));
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get success message based on operation
     */
    protected function getSuccessMessage(string $operation): string
    {
        $entityName = $this->getEntityName();

        $messages = [
            'created' => ucfirst($entityName) . ' berhasil dibuat',
            'updated' => ucfirst($entityName) . ' berhasil diupdate',
            'deleted' => ucfirst($entityName) . ' berhasil dihapus',
        ];

        return $messages[$operation] ?? 'Operasi berhasil';
    }
}