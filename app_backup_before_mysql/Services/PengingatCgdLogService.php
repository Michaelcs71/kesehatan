<?php

namespace App\Services;

use App\Models\JadwalCgd;
use App\Models\PengingatCgdLog;
use App\Models\User;
use App\Repos\PengingatCgdLogRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;

class PengingatCgdLogService
{
    const FOTO_PATH = 'pengingat-cgd';
    const MAX_WIDTH = 1280;
    const QUALITY = 75;

    public static function getAllLogs(array $params): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $forUserId    = null;
        $forPmoUserId = null;

        if ($user->isPasien()) {
            $forUserId = $user->id;
        } elseif ($user->isPmo()) {
            $forPmoUserId = $user->id;
        }
        // Admin/Superadmin: tanpa filter (lihat semua)

        $data = PengingatCgdLogRepository::getAllLogs(
            search: $params['search'] ?? '',
            status: $params['status'] ?? null,
            kategoriHasil: $params['kategori_hasil'] ?? null,
            cgdId: $params['id_cgd'] ?? null,
            tanggalStart: $params['tgl_start'] ?? null,
            tanggalEnd: $params['tgl_end'] ?? null,
            forUserId: $forUserId,
            forPmoUserId: $forPmoUserId,
            page: ($params['pagenum'] ?? 0) + 1,
            perPage: $params['pagesize'] ?? 10,
        );

        return [
            'TotalRows' => $data->total(),
            'Rows'      => $data->items(),
        ];
    }

    public static function findLogById(string $id): ?PengingatCgdLog
    {
        if (empty($id) || in_array($id, ['create', 'edit'])) return null;
        return PengingatCgdLogRepository::findLogById($id);
    }

    public static function getJadwalCgdOptions(): array
    {
        return PengingatCgdLogRepository::getJadwalCgdOptions();
    }

    /**
     * Create log + upload foto + auto-calc kategori & patuh
     */
    public static function createLog(array $data, UploadedFile $foto): PengingatCgdLog
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Load jadwal CGD untuk snapshot
        $cgd = JadwalCgd::where('status', 'aktif')->find($data['id_cgd']);
        if (!$cgd) {
            throw new \Exception('Jadwal CGD tidak ditemukan atau sudah nonaktif.');
        }

        // Tentukan pasien yang dicek
        // Logic: kalau yang login pasien → pasien itu sendiri
        //        kalau yang login PMO/Admin → bisa pilih pasien (untuk Phase 1, default ke user yang login)
        $pasien = $user;
        if (isset($data['id_pasien']) && $data['id_pasien']) {
            $pasien = User::find($data['id_pasien']);
            if (!$pasien) {
                throw new \Exception('Pasien tidak ditemukan.');
            }
        }

        // Load biodata untuk jenis_kelamin
        $pasien->load('biodata');
        $jenisKelamin = $pasien->biodata?->jenis_kelamin;

        // Auto-calc kategori & patuh_selisih
        $hasil = (int) $data['hasil_mgdl'];
        $kategori = PengingatCgdLog::determineKategori($hasil);
        $patuhSelisih = PengingatCgdLog::calculatePatuhSelisih($hasil, $jenisKelamin);

        // Upload foto + compress
        $fotoPath = self::uploadFoto($foto);

        return PengingatCgdLogRepository::createLog([
            'id_cgd'         => $cgd->id,
            'id_user'        => $pasien->id,
            'nama_pasien'    => $pasien->name,
            'jenis_kelamin'  => $jenisKelamin,
            'tempat_cgd'     => $cgd->tempat,
            'tgl_cgd'        => $data['tgl_cgd'],
            'jam_cgd'        => $data['jam_cgd'],
            'hasil_mgdl'     => $hasil,
            'kategori_hasil' => $kategori,
            'patuh_selisih'  => $patuhSelisih,
            'foto_layar'     => $fotoPath,
            'status'         => 'aktif',
            'created_by'     => $user->id,
        ]);
    }

    /**
     * Update log
     */
    public static function updateLog(string $id, array $data, ?UploadedFile $foto = null): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $log = PengingatCgdLogRepository::findLogById($id);
        if (!$log) throw new \Exception('Log tidak ditemukan.');

        self::authorizeLogAccess($log, $user);

        $updateData = [
            'tgl_cgd'    => $data['tgl_cgd'],
            'jam_cgd'    => $data['jam_cgd'],
            'hasil_mgdl' => (int) $data['hasil_mgdl'],
            'status'     => $data['status'] ?? $log->status,
            'updated_by' => $user->id,
        ];

        // Re-calc kategori & patuh kalau hasil berubah
        if ((int) $data['hasil_mgdl'] !== $log->hasil_mgdl) {
            $updateData['kategori_hasil'] = PengingatCgdLog::determineKategori((int) $data['hasil_mgdl']);
            $updateData['patuh_selisih']  = PengingatCgdLog::calculatePatuhSelisih(
                (int) $data['hasil_mgdl'],
                $log->jenis_kelamin
            );
        }

        // Foto baru: upload + hapus lama
        if ($foto) {
            $oldPath = $log->foto_layar;
            $updateData['foto_layar'] = self::uploadFoto($foto);

            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        return PengingatCgdLogRepository::updateLog($id, $updateData);
    }

    public static function deactivate(string $id): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $log = PengingatCgdLogRepository::findLogById($id);
        if (!$log) throw new \Exception('Log tidak ditemukan.');
        self::authorizeLogAccess($log, $user);
        return PengingatCgdLogRepository::deactivate($id, $user->id);
    }

    public static function activate(string $id): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $log = PengingatCgdLogRepository::findLogById($id);
        if (!$log) throw new \Exception('Log tidak ditemukan.');
        self::authorizeLogAccess($log, $user);
        return PengingatCgdLogRepository::activate($id, $user->id);
    }

    public static function deleteLog(string $id): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $log = PengingatCgdLogRepository::findLogById($id);
        if (!$log) throw new \Exception('Log tidak ditemukan.');
        self::authorizeLogAccess($log, $user);
        return PengingatCgdLogRepository::deleteLog($id, $user->id);
    }

    /**
     * Upload foto + compress (Intervention v4)
     */
    protected static function uploadFoto(UploadedFile $file): string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->decode($file->getRealPath());

        if ($image->width() > self::MAX_WIDTH) {
            $image->scaleDown(width: self::MAX_WIDTH);
        }

        $filename = sprintf(
            '%s/%s-%s.jpg',
            self::FOTO_PATH,
            now()->format('Ymd'),
            uniqid('foto_')
        );

        $encoded = $image->encode(new JpegEncoder(quality: self::QUALITY));
        Storage::disk('public')->put($filename, (string) $encoded);

        return $filename;
    }

    /**
     * Stats untuk dashboard
     */
    public static function getStats(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $base = PengingatCgdLog::query();

        if ($user->isPasien()) {
            $base->where('id_user', $user->id);
        } elseif ($user->isPmo()) {
            $base->whereHas('user.pasienPmoAsPasien', function ($q) use ($user) {
                $q->where('pmo_user_id', $user->id)
                    ->where('is_active', true);
            });
        }

        return [
            'total'             => (clone $base)->count(),
            'normal'            => (clone $base)->where('kategori_hasil', 'normal')->count(),
            'tidak_terkontrol'  => (clone $base)->where('kategori_hasil', 'tidak_terkontrol')->count(),
            'tinggi'            => (clone $base)->where('kategori_hasil', 'tinggi')->count(),
            'berbahaya'         => (clone $base)->where('kategori_hasil', 'berbahaya')->count(),
        ];
    }

    /**
     * Authorization: user boleh akses log ini?
     */
    protected static function authorizeLogAccess(PengingatCgdLog $log, \App\Models\User $user): void
    {
        if ($user->isSuperadmin() || $user->isAdmin()) return;

        if ($user->isPasien() && $log->id_user !== $user->id) {
            throw new \Exception('Anda tidak punya akses ke log ini.');
        }

        if ($user->isPmo()) {
            $pasien = User::with('pasienPmoAsPasien')->find($log->id_user);
            $mapping = $pasien?->pasienPmoAsPasien()
                ->where('is_active', true)
                ->where('pmo_user_id', $user->id)
                ->first();
            if (!$mapping) {
                throw new \Exception('Anda tidak punya akses ke log pasien ini.');
            }
        }
    }
}
