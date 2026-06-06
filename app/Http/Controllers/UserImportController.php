<?php

namespace App\Http\Controllers;

use App\Services\UserImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserImportController extends Controller
{
    /**
     * Download template Excel
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        $path = storage_path('app/templates/user_import_template.xlsx');

        // Generate template kalau belum ada
        if (! file_exists($path)) {
            $this->generateTemplate($path);
        }

        return response()->download($path, 'template_import_user.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Upload Excel & parse preview (tidak save ke DB).
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'], // max 5MB
        ], [
            'file.required' => 'File Excel wajib di-upload.',
            'file.mimes' => 'File harus berformat .xlsx atau .xls',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        try {
            $result = UserImportService::parsePreview($request->file('file'));

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal parsing file: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Re-validate satu row (saat user edit di modal).
     */
    public function validateRow(Request $request): JsonResponse
    {
        $request->validate([
            'data' => ['required', 'array'],
            'exclude_namas' => ['nullable', 'array'],
            'exclude_was' => ['nullable', 'array'],
            'exclude_niks' => ['nullable', 'array'],
        ]);

        $excludeOtherRows = [
            'nama' => $request->input('exclude_namas', []),
            'whatsapp_number' => $request->input('exclude_was', []),
            'nik' => $request->input('exclude_niks', []),
        ];

        $result = UserImportService::validateRowData($request->input('data'), $excludeOtherRows);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Konfirmasi import: insert semua row yang valid.
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'rows' => ['required', 'array', 'min:1'],
        ]);

        try {
            $result = UserImportService::confirmImport($request->input('rows'));

            return response()->json([
                'success' => true,
                'message' => "Berhasil import {$result['imported']} user.",
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan import: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Generate template Excel di storage/app/templates/
     */
    protected function generateTemplate(string $path): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import User');

        // Row 1: Title
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT USER - SISTEM KESEHATAN');
        $sheet->mergeCells('A1:R1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        // Row 2: Instruksi
        $sheet->setCellValue('A2', 'Petunjuk: Isi data mulai dari baris 4. Kolom dengan header MERAH wajib diisi. Role hanya bisa "pasien" atau "pmo". Hapus baris contoh sebelum upload.');
        $sheet->mergeCells('A2:R2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB('666666');

        // Row 3: Header (key field - TANPA asterisk biar clean saat di-parse)
        $headers = [
            'A' => ['nama',             true],
            'B' => ['role',             true],
            'C' => ['password',         true],
            'D' => ['whatsapp_number',  true],
            'E' => ['nik',              true],
            'F' => ['no_kk',            false],
            'G' => ['jenis_kelamin',    true],
            'H' => ['tempat_lahir',     true],
            'I' => ['tanggal_lahir',    true],
            'J' => ['alamat_jalan',     false],
            'K' => ['alamat_rt',        false],
            'L' => ['alamat_rw',        false],
            'M' => ['alamat_dusun',     false],
            'N' => ['alamat_desa',      false],
            'O' => ['alamat_kecamatan', false],
            'P' => ['alamat_kabupaten', false],
            'Q' => ['alamat_provinsi',  false],
            'R' => ['alamat_kodepos',   false],
        ];

        foreach ($headers as $col => [$val, $required]) {
            $sheet->setCellValue($col.'3', $val);

            // Warna merah kalau wajib
            if ($required) {
                $sheet->getStyle($col.'3')->getFont()->getColor()->setRGB('C0392B');
            }
        }

        // Style header row
        $sheet->getStyle('A3:R3')->getFont()->setBold(true);
        $sheet->getStyle('A3:R3')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D5E4FA');
        $sheet->getStyle('A3:R3')->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A3:R3')->getAlignment()->setHorizontal('center');

        // Row 4: Contoh data (ada label "[CONTOH - HAPUS BARIS INI]" supaya jelas)
        $sample = [
            'A' => 'Ahmad Fauzi',
            'B' => 'pasien',
            'C' => 'password123',
            'D' => '081234567890',
            'E' => '3507134504810002',
            'F' => '3507130001234567',
            'G' => 'L',
            'H' => 'Malang',
            'I' => '1990-05-15',
            'J' => 'Jl. Merpati No 10',
            'K' => '003',
            'L' => '007',
            'M' => 'Dusun Lebakrejo',
            'N' => 'Desa Ngenep',
            'O' => 'Sukajadi',
            'P' => 'Kota Bandung',
            'Q' => 'Jawa Barat',
            'R' => '40123',
        ];

        foreach ($sample as $col => $val) {
            $sheet->setCellValue($col.'4', $val);
        }

        // Warna sample row jadi kuning + italic biar jelas "ini contoh"
        $sheet->getStyle('A4:R4')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FFF3CD');
        $sheet->getStyle('A4:R4')->getFont()->setItalic(true)->getColor()->setRGB('856404');

        // Row 5: Label peringatan
        $sheet->setCellValue('A5', '⬆ CONTOH DATA - HAPUS BARIS INI SEBELUM UPLOAD!');
        $sheet->mergeCells('A5:R5');
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('C0392B');
        $sheet->getStyle('A5')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A5')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FFE5E5');

        // Set column widths
        foreach (range('A', 'R') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Freeze header & sample row
        $sheet->freezePane('A6');

        // Write file
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
    }
}
