<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class BaseController extends Controller
{
    /**
     * Return success JSON response
     */
    protected function successResponse(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    /**
     * Return error JSON response
     */
    protected function errorResponse(string $message, int $status = 500, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Execute database transaction with automatic rollback on error
     */
    protected function executeTransaction(callable $callback): mixed
    {
        try {
            DB::beginTransaction();
            $result = $callback();
            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Handle controller exceptions consistently
     */
    protected function handleException(\Exception $e, string $defaultMessage = 'Terjadi kesalahan'): JsonResponse
    {
        $message = $e instanceof \Illuminate\Database\QueryException
            ? 'Database error: ' . $e->getMessage()
            : $e->getMessage();

        Log::error($message, [
            'exception' => get_class($e),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $e->getTraceAsString(),
        ]);

        return $this->errorResponse($message ?: $defaultMessage);
    }
}