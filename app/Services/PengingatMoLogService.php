<?php

namespace App\Services;

use App\Models\JadwalMinumObat;
use App\Models\PengingatMoLog;
use App\Models\User;
use App\Repos\PengingatMoLogRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;

  // ← TAMBAH BARIS INI

class PengingatMoLogService
{
    const FOTO_PATH = 'pengingat-mo';

    const MAX_WIDTH = 1280;        // max width foto setelah compress

    const QUALITY = 75;            // JPEG quality (0-100, 75 = balanced)

    public static function getAllLogs(array $params): array
    {
        /** @var User $user */
        $user = Auth::user();

        $forPmoUserId = null;
        $forPasienUserId = null;

        if ($user->isPmo()) {
            $forPmoUserId = $user->id;
        } elseif ($user->isPasien()) {
            $forPasienUserId = $user->id;
        }

        $data = PengingatMoLogRepository::getAllLogs(
            search: $params['search'] ?? '',
            status: $params['status'] ?? null,
            jadwalId: $params['id_jo'] ?? null,
            tanggalStart: $params['tgl_start'] ?? null,
            tanggalEnd: $params['tgl_end'] ?? null,
            patuhKategori: $params['patuh_kategori'] ?? null,
            forPmoUserId: $forPmoUserId,
            forPasienUserId: $forPasienUserId,
            page: ($params['pagenum'] ?? 0) + 1,
            perPage: $params['pagesize'] ?? 10,
        );

        return [
            'TotalRows' => $data->total(),
            'Rows' => $data->items(),
        ];
    }

    public static function findLogById(string $id): ?PengingatMoLog
    {
        if (empty($id) || in_array($id, ['create', 'edit'])) {
            return null;
        }

        return PengingatMoLogRepository::findLogById($id);
    }

    /**
     * Get jadwal options for dropdown (filter per role)
     */
    public static function getJadwalOptions(): array
    {
        /** @var User $user */
        $user = Auth::user();

        $pmoUserId = null;
        $pasienUserId = null;

        if ($user->isPmo()) {
            $pmoUserId = $user->id;
        } elseif ($user->isPasien()) {
            $pasienUserId = $user->id;
        }

        return PengingatMoLogRepository::getJadwalOptions($pmoUserId, $pasienUserId);
    }

    /**
     * Create log baru + upload foto
     */
    public static function createLog(array $data, UploadedFile $foto): PengingatMoLog
    {
        /** @var User $user */
        $user = Auth::user();

        // Load jadwal untuk snapshot data
        $jadwal = JadwalMinumObat::with(['pasienPmo', 'obat'])
            ->where('status', 'aktif')
            ->find($data['id_jo']);

        if (! $jadwal) {
            throw new \Exception('Jadwal minum obat tidak ditemukan atau sudah nonaktif.');
        }

        // Auth check
        self::authorizeJadwalAccess($jadwal, $user);

        // Upload foto + compress
        $fotoPath = self::uploadFoto($foto);

        // Auto-calculate patuh_menit kalau ada jam_slot_target
        $patuhMenit = PengingatMoLog::calculatePatuhMenit(
            $data['jam_slot_target'] ?? null,
            $data['jam_minum_obat']
        );

        return PengingatMoLogRepository::createLog([
            'id_jo' => $jadwal->id,
            'id_user' => $user->id,
            'nama_pasien' => $jadwal->pasienPmo?->nama_pasien ?? '-',
            'nama_obat' => $jadwal->obat?->nama ?? '-',
            'tgl_minum_obat' => $data['tgl_minum_obat'],
            'jam_minum_obat' => $data['jam_minum_obat'],
            'jam_slot_target' => $data['jam_slot_target'] ?? null,
            'patuh_menit' => $patuhMenit,
            'foto_obat' => $fotoPath,
            'status' => 'aktif',
            'created_by' => $user->id,
        ]);
    }

    /**
     * Update log
     */
    public static function updateLog(string $id, array $data, ?UploadedFile $foto = null): bool
    {
        /** @var User $user */
        $user = Auth::user();

        $log = PengingatMoLogRepository::findLogById($id);
        if (! $log) {
            throw new \Exception('Log tidak ditemukan.');
        }

        self::authorizeLogAccess($log, $user);

        $updateData = [
            'tgl_minum_obat' => $data['tgl_minum_obat'],
            'jam_minum_obat' => $data['jam_minum_obat'],
            'jam_slot_target' => $data['jam_slot_target'] ?? null,
            'status' => $data['status'] ?? $log->status,
            'updated_by' => $user->id,
        ];

        // Recalc patuh_menit
        $updateData['patuh_menit'] = PengingatMoLog::calculatePatuhMenit(
            $updateData['jam_slot_target'],
            $updateData['jam_minum_obat']
        );

        // Foto baru: upload dan hapus yang lama
        if ($foto) {
            $oldPath = $log->foto_obat;
            $updateData['foto_obat'] = self::uploadFoto($foto);

            // Delete old file
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        return PengingatMoLogRepository::updateLog($id, $updateData);
    }

    public static function deactivate(string $id): bool
    {
        /** @var User $user */
        $user = Auth::user();
        $log = PengingatMoLogRepository::findLogById($id);
        if (! $log) {
            throw new \Exception('Log tidak ditemukan.');
        }
        self::authorizeLogAccess($log, $user);

        return PengingatMoLogRepository::deactivate($id, $user->id);
    }

    public static function activate(string $id): bool
    {
        /** @var User $user */
        $user = Auth::user();
        $log = PengingatMoLogRepository::findLogById($id);
        if (! $log) {
            throw new \Exception('Log tidak ditemukan.');
        }
        self::authorizeLogAccess($log, $user);

        return PengingatMoLogRepository::activate($id, $user->id);
    }

    public static function deleteLog(string $id): bool
    {
        /** @var User $user */
        $user = Auth::user();
        $log = PengingatMoLogRepository::findLogById($id);
        if (! $log) {
            throw new \Exception('Log tidak ditemukan.');
        }
        self::authorizeLogAccess($log, $user);

        // Optional: delete foto file
        // Kita biarkan dulu untuk safety (soft delete bisa di-restore)
        // if ($log->foto_obat) Storage::disk('public')->delete($log->foto_obat);

        return PengingatMoLogRepository::deleteLog($id, $user->id);
    }

    /**
     * Upload foto + compress otomatis (Intervention Image v3)
     */
    protected static function uploadFoto(UploadedFile $file): string
    {
        // Intervention Image v4 syntax
        $manager = new ImageManager(new Driver);

        // decode() menggantikan read() di v4
        $image = $manager->decode($file->getRealPath());

        // Auto-orient EXIF (foto HP biasanya butuh ini)
        $image->orient();

        // Resize kalau width > MAX_WIDTH, maintain aspect ratio (scaleDown tidak upscale)
        if ($image->width() > self::MAX_WIDTH) {
            $image->scaleDown(width: self::MAX_WIDTH);
        }

        // Generate filename unik
        $filename = sprintf(
            '%s/%s-%s.jpg',
            self::FOTO_PATH,
            now()->format('Ymd'),
            uniqid('foto_')
        );

        // Encode JPEG quality 75 (v4: pakai encode() + JpegEncoder)
        $encoded = $image->encode(new JpegEncoder(quality: self::QUALITY));

        // Save ke storage/app/public/pengingat-mo/
        Storage::disk('public')->put($filename, (string) $encoded);

        return $filename;
    }

    /**
     * Stats untuk dashboard widget
     */
    public static function getStats(): array
    {
        /** @var User $user */
        $user = Auth::user();

        $base = PengingatMoLog::query();

        // Apply role filter
        if ($user->isPmo()) {
            $base->whereHas('jadwalMo.pasienPmo', fn ($q) => $q->where('pmo_user_id', $user->id));
        } elseif ($user->isPasien()) {
            $base->whereHas('jadwalMo.pasienPmo', fn ($q) => $q->where('id_user', $user->id));
        }

        $today = (clone $base)->today();

        return [
            'total_today' => (clone $today)->count(),
            'tepat_today' => (clone $today)->whereRaw('ABS(patuh_menit) <= 15')->count(),
            'telat_today' => (clone $today)->whereRaw('ABS(patuh_menit) > 15')->count(),
            'total_all' => (clone $base)->count(),
        ];
    }

    /**
     * Authorization: user boleh akses jadwal ini?
     */
    protected static function authorizeJadwalAccess(JadwalMinumObat $jadwal, User $user): void
    {
        if ($user->isSuperadmin() || $user->isAdmin()) {
            return;
        }

        $mapping = $jadwal->pasienPmo;
        if (! $mapping) {
            throw new \Exception('Mapping pasien-PMO tidak ditemukan.');
        }

        if ($user->isPmo() && $mapping->pmo_user_id !== $user->id) {
            throw new \Exception('Anda tidak punya akses ke jadwal ini.');
        }

        if ($user->isPasien() && $mapping->id_user !== $user->id) {
            throw new \Exception('Anda tidak punya akses ke jadwal ini.');
        }
    }

    /**
     * Authorization: user boleh akses log ini?
     */
    protected static function authorizeLogAccess(PengingatMoLog $log, User $user): void
    {
        if ($user->isSuperadmin() || $user->isAdmin()) {
            return;
        }

        $mapping = $log->jadwalMo?->pasienPmo;
        if (! $mapping) {
            throw new \Exception('Mapping tidak ditemukan.');
        }

        if ($user->isPmo() && $mapping->pmo_user_id !== $user->id) {
            throw new \Exception('Anda tidak punya akses ke log ini.');
        }

        if ($user->isPasien() && $mapping->id_user !== $user->id) {
            throw new \Exception('Anda tidak punya akses ke log ini.');
        }
    }
}
