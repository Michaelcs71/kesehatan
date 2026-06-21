<?php

namespace App\Repos;

use App\Models\JadwalCgd;
use App\Models\PasienPmo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class JadwalCgdRepository
{
    /**
     * Get all jadwal CGD dengan pagination + filter
     */
    public static function getAllJadwal(
        string $search = '',
        ?string $status = null,
        ?string $timeFilter = null,   // 'upcoming' | 'past' | 'today'
        int $page = 1,
        int $perPage = 10
    ): LengthAwarePaginator {

        $query = JadwalCgd::query()->with('creator:id,name');

        $query->search($search);

        if (! empty($status)) {
            $query->where('status', $status);
        }

        if ($timeFilter === 'upcoming') {
            $query->where('tgl_jadwal_cgd', '>=', now()->format('Y-m-d'));
        } elseif ($timeFilter === 'past') {
            $query->where('tgl_jadwal_cgd', '<', now()->format('Y-m-d'));
        } elseif ($timeFilter === 'today') {
            $query->where('tgl_jadwal_cgd', now()->format('Y-m-d'));
        }

        return $query
            ->orderBy('tgl_jadwal_cgd', 'desc')
            ->orderBy('jam_mulai', 'desc')
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    /**
     * Find by ID dengan eager load
     */
    public static function findJadwalById(string $id): ?JadwalCgd
    {
        return JadwalCgd::with([
            'creator:id,name',
            'updater:id,name',
            'peserta:id,jadwal_cgd_id,id_pasien_pmo,nama_pasien,nama_pmo,dikirim_dibuat_pada,dikirim_h1_pada',
        ])->find($id);
    }

    /**
     * Create jadwal baru
     */
    public static function createJadwal(array $data, array $pesertaIds = []): JadwalCgd
    {
        return DB::transaction(function () use ($data, $pesertaIds) {
            $jadwal = JadwalCgd::create($data);
            self::syncPeserta($jadwal, $pesertaIds);

            return $jadwal;
        });
    }

    /**
     * Update jadwal
     */
    public static function updateJadwal(string $id, array $data, ?array $pesertaIds = null): bool
    {
        return DB::transaction(function () use ($id, $data, $pesertaIds) {
            $jadwal = JadwalCgd::find($id);
            if (! $jadwal) {
                return false;
            }

            $ok = $jadwal->update($data);

            if ($pesertaIds !== null) {
                self::syncPeserta($jadwal, $pesertaIds);
            }

            return $ok;
        });
    }

    /**
     * Sinkronkan peserta jadwal CGD: tambah yang baru (snapshot nama,
     * penanda kirim kosong), hapus yang tak dipilih lagi. Peserta yang
     * tetap ada TIDAK disentuh (penanda kirim dipertahankan).
     *
     * @param  array<int,string>  $pesertaIds  daftar id pasien_pmo
     */
    public static function syncPeserta(JadwalCgd $jadwal, array $pesertaIds): void
    {
        $pasienPmos = PasienPmo::whereIn('id', $pesertaIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $existing = $jadwal->peserta()->get()->keyBy('id_pasien_pmo');

        // Hapus peserta yang tidak dipilih lagi.
        $toRemove = $existing->keys()->diff($pasienPmos->keys());
        if ($toRemove->isNotEmpty()) {
            $jadwal->peserta()->whereIn('id_pasien_pmo', $toRemove->all())->delete();
        }

        // Tambah peserta baru.
        foreach ($pasienPmos as $id => $pp) {
            if (! $existing->has($id)) {
                $jadwal->peserta()->create([
                    'id_pasien_pmo' => $pp->id,
                    'nama_pasien' => $pp->nama_pasien,
                    'nama_pmo' => $pp->nama_pmo,
                ]);
            }
        }
    }

    /**
     * Status: deactivate
     */
    public static function deactivate(string $id, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $jadwal = JadwalCgd::find($id);
            if (! $jadwal) {
                return false;
            }

            return $jadwal->update([
                'status' => 'nonaktif',
                'updated_by' => $userId,
            ]);
        });
    }

    /**
     * Status: activate
     */
    public static function activate(string $id, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $jadwal = JadwalCgd::find($id);
            if (! $jadwal) {
                return false;
            }

            return $jadwal->update([
                'status' => 'aktif',
                'updated_by' => $userId,
            ]);
        });
    }

    /**
     * Status: mark selesai
     */
    public static function markSelesai(string $id, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $jadwal = JadwalCgd::find($id);
            if (! $jadwal) {
                return false;
            }

            return $jadwal->update([
                'status' => 'selesai',
                'updated_by' => $userId,
            ]);
        });
    }

    /**
     * Soft delete
     */
    public static function deleteJadwal(string $id, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $jadwal = JadwalCgd::find($id);
            if (! $jadwal) {
                return false;
            }

            $jadwal->deleted_by = $userId;
            $jadwal->save();

            return (bool) $jadwal->delete();
        });
    }

    /**
     * Get upcoming CGD untuk widget dashboard (jadwal mendatang yang aktif)
     */
    public static function getUpcoming(int $limit = 5): array
    {
        return JadwalCgd::query()
            ->upcoming()
            ->with('creator:id,name')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Opsi peserta (semua pasien_pmo aktif) untuk multi-select form CGD.
     */
    public static function getPasienPmoOptions(): array
    {
        return PasienPmo::query()
            ->where('is_active', true)
            ->orderBy('nama_pasien')
            ->get(['id', 'nama_pasien', 'nama_pmo'])
            ->map(fn ($pp) => [
                'id' => $pp->id,
                'nama_pasien' => $pp->nama_pasien,
                'nama_pmo' => $pp->nama_pmo,
                'label' => $pp->nama_pasien.' (PMO: '.($pp->nama_pmo ?? '-').')',
            ])
            ->toArray();
    }

    /**
     * Auto-mark CGD lewat sebagai 'selesai' (untuk scheduled job nanti)
     * Return jumlah yang di-update
     */
    public static function autoMarkSelesaiPastEvents(): int
    {
        return JadwalCgd::query()
            ->where('status', 'aktif')
            ->where('tgl_jadwal_cgd', '<', now()->format('Y-m-d'))
            ->update([
                'status' => 'selesai',
                'updated_at' => now(),
            ]);
    }
}
