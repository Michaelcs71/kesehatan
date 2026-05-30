<?php

namespace App\Services;

use App\Enums\StatusObat;
use App\Models\MasterObat;
use App\Repos\MasterObatRepository;
use App\Services\Concerns\HasStandardizedMethods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MasterObatService
{
    use HasStandardizedMethods;

    protected static function getEntityName(): string
    {
        return 'Obat';
    }

    protected static function getEntityPluralName(): string
    {
        return 'Obats';
    }

    /**
     * Get all obats with pagination & filter
     */
    public static function getAllObats(array $params): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $data = MasterObatRepository::getAllObats(
            search: $params['search'] ?? '',
            status: $params['status'] ?? '',
            kategoriId: $params['kategori_id'] ?? '',
            page: ($params['pagenum'] ?? 0) + 1,
            perPage: $params['pagesize'] ?? 10,
            user: $user
        );

        return [
            'TotalRows' => $data->total(),
            'Rows'      => $data->items(),
        ];
    }

    /**
     * Find obat by ID
     */
    public static function findObatById(string $id): ?MasterObat
    {
        if (empty($id) || in_array($id, ['create', 'edit'])) {
            return null;
        }

        try {
            return MasterObatRepository::findObatById($id);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Create new obat
     */
    public static function createObat(array $data, ?Request $request = null): MasterObat
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Upload foto kalau ada
        if ($request && $request->hasFile('foto')) {
            $data['foto_path'] = $request->file('foto')->store('master-obats', 'public');
        }

        // Auto-set status & verifikasi berdasarkan role
        if ($user->isAdmin()) {
            $data['status']      = StatusObat::APPROVED->value;
            $data['verified_by'] = $user->id;
            $data['verified_at'] = now();
        } else {
            $data['status'] = StatusObat::PENDING->value;
        }

        $data['created_by'] = $user->id;

        return MasterObatRepository::createObat($data);
    }

    /**
     * Update existing obat
     */
    public static function updateObat(string $id, array $data, ?Request $request = null): bool
    {
        $obat = MasterObatRepository::findObatById($id);
        if (!$obat) {
            return false;
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Handle foto baru
        if ($request && $request->hasFile('foto')) {
            if ($obat->foto_path && Storage::disk('public')->exists($obat->foto_path)) {
                Storage::disk('public')->delete($obat->foto_path);
            }
            $data['foto_path'] = $request->file('foto')->store('master-obats', 'public');
        }

        // Kalau pasien/pmo edit obat yg ditolak, auto-resubmit jadi pending
        if (!$user->isAdmin() && $obat->isRejected()) {
            $data['status']             = StatusObat::PENDING->value;
            $data['catatan_verifikasi'] = null;
            $data['verified_by']        = null;
            $data['verified_at']        = null;
        }

        return MasterObatRepository::updateObat($id, $data);
    }

    /**
     * Delete obat (soft delete) + hapus foto fisik
     */
    public static function deleteObat(string $id): bool
    {
        $obat = MasterObatRepository::findObatById($id);
        if (!$obat) {
            return false;
        }

        if ($obat->foto_path && Storage::disk('public')->exists($obat->foto_path)) {
            Storage::disk('public')->delete($obat->foto_path);
        }

        return MasterObatRepository::deleteObat($id);
    }

    /**
     * Verify obat (approve / reject)
     */
    public static function verifyObat(string $id, array $data): bool
    {
        return MasterObatRepository::updateObat($id, [
            'status'             => $data['status'],
            'verified_by'        => Auth::id(),
            'verified_at'        => now(),
            'catatan_verifikasi' => $data['catatan_verifikasi'] ?? null,
        ]);
    }

    /**
     * Get stats count per status
     */
    public static function getStats(): array
    {
        return [
            'pending'  => MasterObat::pending()->count(),
            'approved' => MasterObat::approved()->count(),
            'rejected' => MasterObat::rejected()->count(),
            'total'    => MasterObat::count(),
        ];
    }
}