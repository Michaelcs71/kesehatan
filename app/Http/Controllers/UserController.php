<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Http\Controllers\Concerns\HasCrudOperations;
use App\Http\Controllers\Concerns\HasCrudViews;
use App\Http\Requests\User\IndexRequest;
use App\Http\Requests\User\StoreUpdateRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    use HasCrudOperations, HasCrudViews;

    protected function getViewPath(): string
    {
        return 'master-user';
    }

    protected function getRouteName(): string
    {
        return 'master-user';
    }

    protected function getService(): string
    {
        return UserService::class;
    }

    protected function getEntityName(): string
    {
        return 'user';
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

    /**
     * Custom store: pisahkan userData & biodataData
     */
    public function store(StoreUpdateRequest $request): JsonResponse
    {
        try {
            UserService::createUser($request->userData(), $request->biodataData());

            return response()->json([
                'success' => true,
                'message' => 'User berhasil dibuat.',
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
        $user = UserService::findUserById($id);
        /** @var User $loggedInUser */
        $loggedInUser = auth()->user();

        if (! $user) {
            return $this->redirectToIndexWithError();
        }

        if (! $loggedInUser->isSuperadmin() && $user->id !== $loggedInUser->id) {
            if (in_array($user->role->value, ['admin', 'superadmin'])) {
                return $this->redirectToIndexWithError('Anda tidak memiliki izin untuk melihat user ini.');
            }
        }

        return $this->showView($id);
    }

    public function showData(string $id): JsonResponse
    {
        $user = UserService::findUserById($id);
        /** @var User $loggedInUser */
        $loggedInUser = auth()->user();

        if (! $user) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        if (! $loggedInUser->isSuperadmin() && $user->id !== $loggedInUser->id) {
            if (in_array($user->role->value, ['admin', 'superadmin'])) {
                return response()->json(['message' => 'Akses ditolak'], 403);
            }
        }

        // Load relasi biodata
        $user->loadMissing('biodata');

        $userData = $user->toArray();
        $userData['role_label'] = $user->role?->label() ?? '-';

        if ($loggedInUser->isSuperadmin()) {
            $userData['permission_summary'] = PermissionHelper::getUserPermissionSummary($user);
        }

        return response()->json($userData);
    }

    public function edit(string $id)
    {
        return $this->editView($id);
    }

    /**
     * Custom update: pisahkan userData & biodataData
     */
    public function update(StoreUpdateRequest $request, string $id): JsonResponse
    {
        try {
            UserService::updateUser($id, $request->userData(), $request->biodataData());

            return response()->json([
                'success' => true,
                'message' => 'User berhasil diupdate.',
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
        return $this->handleDestroy($id);
    }

    public function availableRoles(): JsonResponse
    {
        return response()->json(UserService::getAvailableRoles());
    }

    // ============================================================
    // PERMISSION MANAGEMENT (Superadmin only)
    // ============================================================

    public function getPermissions(string $id): JsonResponse
    {
        try {
            $data = UserService::getPermissionEditorData($id);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }
    }

    public function updatePermissions(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'string|max:100',
        ]);

        try {
            UserService::updateUserPermissions($id, $request->input('permissions', []));

            return response()->json([
                'success' => true,
                'message' => 'Permission user berhasil diperbarui.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }
    }

    public function resetPermissions(string $id): JsonResponse
    {
        try {
            UserService::resetUserPermissions($id);

            return response()->json([
                'success' => true,
                'message' => 'Permission user berhasil direset ke default role.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }
    }

    // ============================================================
    // RESET PASSWORD (Admin only)
    // ============================================================

    /**
     * Reset password user (admin/superadmin only).
     */
    public function resetPassword(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        try {
            UserService::resetPassword($id, $request->password);

            return response()->json([
                'success' => true,
                'message' => 'Password user berhasil direset.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }
    }
}
