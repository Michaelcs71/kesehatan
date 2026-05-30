<?php

namespace App\Repos;

use App\Models\JadwalMinumObat;
use App\Models\MasterObat;
use App\Models\PasienPmo;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class JadwalMinumObatRepository
{
    /**
     * Get all jadwal dengan pagination + filter
     */
    public static function getAllJadwal(
        string $search = '',
        ?string $status = null,
        ?string $pasienPmoId = null,
        ?string $obatId = null,
        ?string $createdBy = null,
        ?string $forPmoUserId = null,
        ?string $forPasienUserId = null,
        int $page = 1,
        int $perPage = 10
    ): LengthAwarePaginator {

        $query = JadwalMinumObat::query()
            ->with([
                'pasienPmo:id,id_user,pmo_user_id,nama_pasien,nama_pmo',
                'obat:id,nama,satuan_id,dosis_default',
                'obat.satuan:id,nama,singkatan',
            ]);

        $query->search($search);

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($pasienPmoId)) {
            $query->where('id_pasien_pmo', $pasienPmoId);
        }

        if (!empty($obatId)) {
            $query->where('obat_id', $obatId);
        }

        if (!empty($createdBy)) {
            $query->where('created_by', $createdBy);
        }

        // Filter per role: PMO cuma lihat jadwal pasien-nya
        if (!empty($forPmoUserId)) {
            $query->whereHas('pasienPmo', function ($q) use ($forPmoUserId) {
                $q->where('pmo_user_id', $forPmoUserId);
            });
        }

        // Filter per role: Pasien cuma lihat jadwal sendiri
        if (!empty($forPasienUserId)) {
            $query->whereHas('pasienPmo', function ($q) use ($forPasienUserId) {
                $q->where('id_user', $forPasienUserId);
            });
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    /**
     * Find jadwal by ID dengan eager load
     */
    public static function findJadwalById(string $id): ?JadwalMinumObat
    {
        return JadwalMinumObat::with([
            'pasienPmo:id,id_user,pmo_user_id,nama_pasien,nama_pmo',
            'obat:id,nama,kategori_id,satuan_id,dosis_default,aturan_minum',
            'obat.satuan:id,nama,singkatan',
            'obat.kategori:id,nama',
            'creator:id,name',
            'updater:id,name',
        ])->find($id);
    }

    /**
     * Daftar pasien-PMO mapping aktif untuk dropdown
     * Filter per role di sini
     */
    public static function getPasienPmoOptions(?string $pmoUserId = null, ?string $pasienUserId = null): array
    {
        $query = PasienPmo::query()
            ->where('is_active', true)
            ->with([
                'pasien:id,name',
                'pmo:id,name',
            ]);

        if ($pmoUserId) {
            $query->where('pmo_user_id', $pmoUserId);
        }

        if ($pasienUserId) {
            $query->where('id_user', $pasienUserId);
        }

        return $query
            ->orderBy('nama_pasien')
            ->get(['id', 'id_user', 'pmo_user_id', 'nama_pasien', 'nama_pmo'])
            ->map(function ($mapping) {
                return [
                    'id'              => $mapping->id,
                    'nama_pasien'     => $mapping->nama_pasien,
                    'nama_pmo'        => $mapping->nama_pmo,
                    'label'           => $mapping->nama_pasien . ' (PMO: ' . $mapping->nama_pmo . ')',
                ];
            })
            ->toArray();
    }

    /**
     * Daftar obat aktif (status approved) untuk dropdown
     */
    public static function getObatOptions(?string $search = null): array
    {
        $query = MasterObat::query()
            ->approved()
            ->with('satuan:id,nama,singkatan');

        if (!empty($search)) {
            $query->search($search);
        }

        return $query
            ->orderBy('nama')
            ->limit(100)  // safety limit
            ->get(['id', 'nama', 'dosis_default', 'satuan_id', 'aturan_minum'])
            ->map(function ($obat) {
                $satuan = $obat->satuan?->singkatan ?? $obat->satuan?->nama;
                $dosis  = $obat->dosis_default ? " {$obat->dosis_default}" : '';
                $satuanStr = $satuan ? " ({$satuan})" : '';
                return [
                    'id'            => $obat->id,
                    'nama'          => $obat->nama,
                    'dosis_default' => $obat->dosis_default,
                    'satuan'        => $satuan,
                    'aturan_minum'  => $obat->aturan_minum,
                    'label'         => $obat->nama . $dosis . $satuanStr,
                ];
            })
            ->toArray();
    }

    /**
     * Bulk create: 1 pasien-PMO mapping → multi obat dalam 1 transaction
     *
     * @param string $pasienPmoId
     * @param array $obatItems Array of ['obat_id', 'tgl_mulai', 'jam_mulai', 'frekuensi_per_hari', 'durasi_hari', 'catatan_dosis']
     * @param array $auditData ['created_by']
     */
    public static function bulkCreate(string $pasienPmoId, array $obatItems, array $auditData): array
    {
        return DB::transaction(function () use ($pasienPmoId, $obatItems, $auditData) {
            $created = [];

            // Load mapping
            $mapping = PasienPmo::find($pasienPmoId);
            if (!$mapping) {
                throw new \Exception('Mapping pasien-PMO tidak ditemukan.');
            }
            if (!$mapping->is_active) {
                throw new \Exception('Mapping pasien-PMO tidak aktif.');
            }

            // Load semua obat sekaligus untuk validasi
            $obatIds = array_unique(array_column($obatItems, 'obat_id'));
            $obats = MasterObat::whereIn('id', $obatIds)->get()->keyBy('id');

            foreach ($obatItems as $item) {
                $obat = $obats->get($item['obat_id']);
                if (!$obat) {
                    throw new \Exception("Obat dengan ID {$item['obat_id']} tidak ditemukan.");
                }

                $jadwal = JadwalMinumObat::create([
                    'id_pasien_pmo'      => $mapping->id,
                    'obat_id'            => $obat->id,
                    'nama_pasien'        => $mapping->nama_pasien,
                    'nama_pmo'           => $mapping->nama_pmo,
                    'tgl_mulai'          => $item['tgl_mulai'],
                    'jam_mulai'          => $item['jam_mulai'],
                    'frekuensi_per_hari' => $item['frekuensi_per_hari'],
                    'catatan_dosis'      => $item['catatan_dosis'] ?? null,
                    'status'             => 'aktif',
                    'created_by'         => $auditData['created_by'] ?? null,
                ]);

                $created[] = $jadwal;
            }

            return $created;
        });
    }

    /**
     * Update single jadwal
     */
    public static function updateJadwal(string $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $jadwal = JadwalMinumObat::find($id);
            if (!$jadwal) return false;

            // Kalau pasien-pmo berubah, update snapshot
            if (!empty($data['id_pasien_pmo']) && $data['id_pasien_pmo'] !== $jadwal->id_pasien_pmo) {
                $mapping = PasienPmo::find($data['id_pasien_pmo']);
                if ($mapping) {
                    $data['nama_pasien'] = $mapping->nama_pasien;
                    $data['nama_pmo']    = $mapping->nama_pmo;
                }
            }

            return $jadwal->update($data);
        });
    }

    /**
     * Set status nonaktif
     */
    public static function deactivate(string $id, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $jadwal = JadwalMinumObat::find($id);
            if (!$jadwal) return false;

            return $jadwal->update([
                'status'     => 'nonaktif',
                'updated_by' => $userId,
            ]);
        });
    }

    /**
     * Set status aktif
     */
    public static function activate(string $id, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $jadwal = JadwalMinumObat::find($id);
            if (!$jadwal) return false;

            return $jadwal->update([
                'status'     => 'aktif',
                'updated_by' => $userId,
            ]);
        });
    }

    /**
     * Set status selesai
     */
    public static function markSelesai(string $id, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $jadwal = JadwalMinumObat::find($id);
            if (!$jadwal) return false;

            return $jadwal->update([
                'status'     => 'selesai',
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
            $jadwal = JadwalMinumObat::find($id);
            if (!$jadwal) return false;

            $jadwal->deleted_by = $userId;
            $jadwal->save();

            return (bool) $jadwal->delete();
        });
    }

    /**
     * Quick-create master obat (untuk fitur quick-add di form jadwal)
     * Langsung status 'approved' (sesuai requirement)
     */
    public static function quickCreateObat(array $data, ?string $userId = null): MasterObat
    {
        return DB::transaction(function () use ($data, $userId) {
            return MasterObat::create([
                'nama'         => $data['nama'],
                'satuan_id'    => $data['satuan_id'] ?? null,
                'kategori_id'  => $data['kategori_id'] ?? null,
                'dosis_default' => $data['dosis_default'] ?? null,
                'status'       => \App\Enums\StatusObat::APPROVED->value,
                'created_by'   => $userId,
                'verified_by'  => $userId,    // auto-verified by creator
                'verified_at'  => now(),
            ]);
        });
    }
}
