<?php

namespace App\Services;

use App\Models\JadwalCgd;
use App\Repos\JadwalCgdRepository;
use Illuminate\Support\Facades\Auth;

class JadwalCgdService
{
    public static function getAllJadwal(array $params): array
    {
        $data = JadwalCgdRepository::getAllJadwal(
            search: $params['search'] ?? '',
            status: $params['status'] ?? null,
            timeFilter: $params['time_filter'] ?? null,
            page: ($params['pagenum'] ?? 0) + 1,
            perPage: $params['pagesize'] ?? 10,
        );

        return [
            'TotalRows' => $data->total(),
            'Rows' => $data->items(),
        ];
    }

    public static function findJadwalById(string $id): ?JadwalCgd
    {
        if (empty($id) || in_array($id, ['create', 'edit'])) {
            return null;
        }

        return JadwalCgdRepository::findJadwalById($id);
    }

    public static function createJadwal(array $data): JadwalCgd
    {
        $peserta = $data['peserta'] ?? [];
        unset($data['peserta']);

        $data['tgl_input'] = now()->format('Y-m-d');
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? 'aktif';

        return JadwalCgdRepository::createJadwal($data, $peserta);
    }

    public static function updateJadwal(string $id, array $data): bool
    {
        $peserta = array_key_exists('peserta', $data) ? ($data['peserta'] ?? []) : null;
        unset($data['peserta']);

        $data['updated_by'] = Auth::id();

        return JadwalCgdRepository::updateJadwal($id, $data, $peserta);
    }

    public static function deactivate(string $id): bool
    {
        return JadwalCgdRepository::deactivate($id, Auth::id());
    }

    public static function activate(string $id): bool
    {
        return JadwalCgdRepository::activate($id, Auth::id());
    }

    public static function markSelesai(string $id): bool
    {
        return JadwalCgdRepository::markSelesai($id, Auth::id());
    }

    public static function deleteJadwal(string $id): bool
    {
        return JadwalCgdRepository::deleteJadwal($id, Auth::id());
    }

    public static function getUpcoming(int $limit = 5): array
    {
        return JadwalCgdRepository::getUpcoming($limit);
    }

    public static function getPasienPmoOptions(): array
    {
        return JadwalCgdRepository::getPasienPmoOptions();
    }

    public static function getStats(): array
    {
        return [
            'total' => JadwalCgd::count(),
            'aktif' => JadwalCgd::where('status', 'aktif')->count(),
            'upcoming' => JadwalCgd::upcoming()->count(),
            'past' => JadwalCgd::past()->count(),
            'selesai' => JadwalCgd::where('status', 'selesai')->count(),
        ];
    }
}
