<?php

namespace App\Services;

use App\Imports\UserImport;
use App\Models\User;
use App\Models\UserBiodata;
use App\Repos\UserRepository;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class UserImportService
{
    /**
     * Parse Excel file dan return preview data dengan validasi per row.
     *
     * @return array{rows: array, summary: array}
     */
    public static function parsePreview(UploadedFile $file): array
    {
        $imported = Excel::toArray(new UserImport(), $file);
        $rawRows  = $imported[0] ?? [];

        // Filter empty rows
        $rawRows = array_filter($rawRows, function ($row) {
            return !empty($row['nama']) || !empty($row['nik']);
        });

        $rows    = [];
        $rowIdx  = 0;

        // Track duplikat di dalam file (cross-row)
        $seenInFile = [
            'nama'    => [],
            'whatsapp_number' => [],
            'nik'     => [],
        ];

        foreach ($rawRows as $row) {
            $rowIdx++;
            $rowData = self::normalizeRowData($row);
            $errors  = self::validateRow($rowData, $seenInFile);

            $rows[] = [
                'row_number' => $rowIdx,
                'data'       => $rowData,
                'errors'     => $errors,
                'status'     => empty($errors) ? 'valid' : (self::hasFatalError($errors) ? 'error' : 'conflict'),
                'skipped'    => false,
            ];

            // Track yang sudah dilihat (untuk deteksi duplikat dalam file)
            if (!empty($rowData['nama'])) {
                $seenInFile['nama'][] = strtolower($rowData['nama']);
            }
            if (!empty($rowData['whatsapp_number'])) {
                $seenInFile['whatsapp_number'][] = $rowData['whatsapp_number'];
            }
            if (!empty($rowData['nik'])) {
                $seenInFile['nik'][] = $rowData['nik'];
            }
        }

        $summary = self::buildSummary($rows);

        return [
            'rows'    => $rows,
            'summary' => $summary,
        ];
    }

    /**
     * Validasi satu row data (untuk re-validate setelah edit).
     */
    public static function validateRowData(array $rowData, array $excludeOtherRows = []): array
    {
        $rowData = self::normalizeRowData($rowData);
        $errors  = self::validateRow($rowData, $excludeOtherRows);

        return [
            'data'   => $rowData,
            'errors' => $errors,
            'status' => empty($errors) ? 'valid' : (self::hasFatalError($errors) ? 'error' : 'conflict'),
        ];
    }

    /**
     * Konfirmasi import: insert semua row yang valid (skipped diabaikan).
     */
    public static function confirmImport(array $rows): array
    {
        $imported  = 0;
        $skipped   = 0;
        $failed    = [];

        DB::transaction(function () use ($rows, &$imported, &$skipped, &$failed) {
            $seenInBatch = [
                'nama'            => [],
                'whatsapp_number' => [],
                'nik'             => [],
            ];

            foreach ($rows as $row) {
                // DEBUG: log row yang sedang diproses
                \Log::info('Import row processing', [
                    'row_number' => $row['row_number'] ?? '?',
                    'status'     => $row['status'] ?? '?',
                    'skipped'    => $row['skipped'] ?? false,
                    'data'       => $row['data'] ?? [],
                ]);

                $isSkipped = filter_var($row['skipped'] ?? false, FILTER_VALIDATE_BOOLEAN);
                if ($isSkipped || $row['status'] !== 'valid') {
                    $skipped++;
                    \Log::info('Import row SKIPPED (status not valid)', [
                        'row_number' => $row['row_number'],
                        'reason' => 'status=' . ($row['status'] ?? '?') . ', skipped=' . ($row['skipped'] ? 'true' : 'false'),
                    ]);
                    continue;
                }

                $data = $row['data'];

                // Re-validate one more time sebelum insert (safety)
                $errors = self::validateRow($data, $seenInBatch);
                if (!empty($errors)) {
                    $skipped++;
                    $failed[] = [
                        'row_number' => $row['row_number'],
                        'name'       => $data['nama'] ?? '-',
                        'errors'     => $errors,
                    ];
                    \Log::warning('Import row FAILED re-validation', [
                        'row_number' => $row['row_number'],
                        'errors' => $errors,
                    ]);
                    continue;
                }

                try {
                    $userData = [
                        'name'            => $data['nama'],
                        'whatsapp_number' => $data['whatsapp_number'],
                        'password'        => $data['password'],
                        'role'            => $data['role'],
                        'is_active'       => true,
                    ];

                    $biodataData = [
                        'nik'             => $data['nik'],
                        'no_kk'           => $data['no_kk'] ?? null,
                        'jenis_kelamin'   => $data['jenis_kelamin'],
                        'tempat_lahir'    => $data['tempat_lahir'],
                        'tanggal_lahir'   => $data['tanggal_lahir'],
                        'alamat_jalan'    => $data['alamat_jalan'] ?? null,
                        'alamat_rt'       => $data['alamat_rt'] ?? null,
                        'alamat_rw'       => $data['alamat_rw'] ?? null,
                        'alamat_dusun'    => $data['alamat_dusun'] ?? null,
                        'alamat_desa'     => $data['alamat_desa'] ?? null,
                        'alamat_kecamatan' => $data['alamat_kecamatan'] ?? null,
                        'alamat_kabupaten' => $data['alamat_kabupaten'] ?? null,
                        'alamat_provinsi' => $data['alamat_provinsi'] ?? null,
                        'alamat_kodepos'  => $data['alamat_kodepos'] ?? null,
                    ];

                    UserRepository::createUser($userData, $biodataData);
                    $imported++;

                    \Log::info('Import row SUCCESS', [
                        'row_number' => $row['row_number'],
                        'name' => $data['nama'],
                    ]);

                    $seenInBatch['nama'][]            = strtolower($data['nama']);
                    $seenInBatch['whatsapp_number'][] = $data['whatsapp_number'];
                    $seenInBatch['nik'][]             = $data['nik'];
                } catch (\Exception $e) {
                    $skipped++;
                    $failed[] = [
                        'row_number' => $row['row_number'],
                        'name'       => $data['nama'] ?? '-',
                        'errors'     => ['general' => $e->getMessage()],
                    ];
                    \Log::error('Import row EXCEPTION', [
                        'row_number' => $row['row_number'],
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        });

        return [
            'imported' => $imported,
            'skipped'  => $skipped,
            'failed'   => $failed,
        ];
    }

    /**
     * Normalisasi satu row: trim, lowercase role, format date, normalisasi WA, dll.
     */
    protected static function normalizeRowData(array $row): array
    {
        $normalized = [
            'nama'            => self::trimOrNull($row['nama'] ?? null),
            'role'            => strtolower(trim($row['role'] ?? '')),
            'password'        => (string) ($row['password'] ?? ''),
            'whatsapp_number' => self::normalizeWhatsApp($row['whatsapp_number'] ?? null),
            'nik'             => self::normalizeNik($row['nik'] ?? null),
            'no_kk'           => self::normalizeNik($row['no_kk'] ?? null),
            'jenis_kelamin'   => strtoupper(trim($row['jenis_kelamin'] ?? '')),
            'tempat_lahir'    => self::trimOrNull($row['tempat_lahir'] ?? null),
            'tanggal_lahir'   => self::normalizeDate($row['tanggal_lahir'] ?? null),

            // Alamat (semua optional)
            'alamat_jalan'      => self::trimOrNull($row['alamat_jalan'] ?? null),
            'alamat_rt'         => self::trimOrNull($row['alamat_rt'] ?? null),
            'alamat_rw'         => self::trimOrNull($row['alamat_rw'] ?? null),
            'alamat_dusun'      => self::trimOrNull($row['alamat_dusun'] ?? null),
            'alamat_desa'       => self::trimOrNull($row['alamat_desa'] ?? null),
            'alamat_kecamatan'  => self::trimOrNull($row['alamat_kecamatan'] ?? null),
            'alamat_kabupaten'  => self::trimOrNull($row['alamat_kabupaten'] ?? null),
            'alamat_provinsi'   => self::trimOrNull($row['alamat_provinsi'] ?? null),
            'alamat_kodepos'    => self::trimOrNull($row['alamat_kodepos'] ?? null),
        ];

        return $normalized;
    }

    protected static function trimOrNull($value): ?string
    {
        if ($value === null || $value === '') return null;
        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }

    protected static function normalizeWhatsApp($value): ?string
    {
        if ($value === null || $value === '') return null;
        $clean = preg_replace('/[\s\+\-\(\)]/', '', (string) $value);
        if (str_starts_with($clean, '62')) {
            $clean = '0' . substr($clean, 2);
        }
        return $clean === '' ? null : $clean;
    }

    protected static function normalizeNik($value): ?string
    {
        if ($value === null || $value === '') return null;
        // Hilangkan spasi dan strip leading zero kalau ada
        $clean = preg_replace('/[\s]/', '', (string) $value);
        return $clean === '' ? null : $clean;
    }

    protected static function normalizeDate($value): ?string
    {
        if ($value === null || $value === '') return null;

        // Excel kadang return numeric date (e.g. 33239 = 1990-12-25)
        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                    ->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validasi satu row data. Return array of errors per field.
     */
    protected static function validateRow(array $data, array $seenInFile): array
    {
        $errors = [];

        // ========== FATAL ERRORS (format/missing) ==========

        if (empty($data['nama'])) {
            $errors['nama'] = 'Nama wajib diisi.';
        }

        if (empty($data['role'])) {
            $errors['role'] = 'Role wajib diisi.';
        } elseif (!in_array($data['role'], ['pasien', 'pmo'])) {
            $errors['role'] = 'Role harus "pasien" atau "pmo".';
        }

        if (empty($data['password']) || strlen($data['password']) < 8) {
            $errors['password'] = 'Password wajib diisi (min. 8 karakter).';
        }

        if (empty($data['whatsapp_number'])) {
            $errors['whatsapp_number'] = 'WhatsApp wajib diisi.';
        } elseif (!preg_match('/^[0-9]{10,15}$/', $data['whatsapp_number'])) {
            $errors['whatsapp_number'] = 'Format WhatsApp tidak valid.';
        }

        if (empty($data['nik'])) {
            $errors['nik'] = 'NIK wajib diisi.';
        } elseif (!preg_match('/^[0-9]{16}$/', $data['nik'])) {
            $errors['nik'] = 'NIK harus 16 digit angka.';
        }

        if (!empty($data['no_kk']) && !preg_match('/^[0-9]{16}$/', $data['no_kk'])) {
            $errors['no_kk'] = 'No KK harus 16 digit angka.';
        }

        if (empty($data['jenis_kelamin'])) {
            $errors['jenis_kelamin'] = 'Jenis kelamin wajib diisi.';
        } elseif (!in_array($data['jenis_kelamin'], ['L', 'P'])) {
            $errors['jenis_kelamin'] = 'Jenis kelamin harus "L" atau "P".';
        }

        if (empty($data['tempat_lahir'])) {
            $errors['tempat_lahir'] = 'Tempat lahir wajib diisi.';
        }

        if (empty($data['tanggal_lahir'])) {
            $errors['tanggal_lahir'] = 'Tanggal lahir wajib diisi (format: YYYY-MM-DD).';
        } else {
            try {
                $tgl = Carbon::parse($data['tanggal_lahir']);
                if ($tgl->isFuture()) {
                    $errors['tanggal_lahir'] = 'Tanggal lahir tidak boleh di masa depan.';
                }
            } catch (\Exception $e) {
                $errors['tanggal_lahir'] = 'Format tanggal lahir tidak valid.';
            }
        }

        // ========== CONFLICTS (duplikat di DB atau di file) ==========

        if (!empty($data['nama'])) {
            // Cek di DB
            if (User::where('name', $data['nama'])->exists()) {
                $errors['nama'] = 'Nama sudah terdaftar di sistem.';
            }
            // Cek di file (duplikat antar row)
            elseif (in_array(strtolower($data['nama']), $seenInFile['nama'] ?? [])) {
                $errors['nama'] = 'Nama duplikat dalam file ini.';
            }
        }

        if (!empty($data['whatsapp_number']) && empty($errors['whatsapp_number'])) {
            if (User::where('whatsapp_number', $data['whatsapp_number'])->exists()) {
                $errors['whatsapp_number'] = 'WhatsApp sudah terdaftar di sistem.';
            } elseif (in_array($data['whatsapp_number'], $seenInFile['whatsapp_number'] ?? [])) {
                $errors['whatsapp_number'] = 'WhatsApp duplikat dalam file ini.';
            }
        }

        if (!empty($data['nik']) && empty($errors['nik'])) {
            if (UserBiodata::where('nik', $data['nik'])->exists()) {
                $errors['nik'] = 'NIK sudah terdaftar di sistem.';
            } elseif (in_array($data['nik'], $seenInFile['nik'] ?? [])) {
                $errors['nik'] = 'NIK duplikat dalam file ini.';
            }
        }

        return $errors;
    }

    /**
     * Apakah error ini "fatal" (tidak bisa di-edit di modal)?
     * Format error & missing field = fatal.
     * Duplikat = conflict (bisa di-edit).
     */
    protected static function hasFatalError(array $errors): bool
    {
        foreach ($errors as $field => $msg) {
            // Kalau pesan mengandung "duplikat" atau "sudah terdaftar", itu conflict (bukan fatal)
            if (str_contains(strtolower($msg), 'duplikat') || str_contains(strtolower($msg), 'sudah terdaftar')) {
                continue;
            }
            // Selain itu fatal
            return true;
        }
        return false;
    }

    protected static function buildSummary(array $rows): array
    {
        $valid    = 0;
        $conflict = 0;
        $error    = 0;

        foreach ($rows as $row) {
            switch ($row['status']) {
                case 'valid':
                    $valid++;
                    break;
                case 'conflict':
                    $conflict++;
                    break;
                case 'error':
                    $error++;
                    break;
            }
        }

        return [
            'total'    => count($rows),
            'valid'    => $valid,
            'conflict' => $conflict,
            'error'    => $error,
        ];
    }
}
