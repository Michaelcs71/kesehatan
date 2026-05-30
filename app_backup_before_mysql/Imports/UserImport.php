<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Simple importer yang return array data mentah dari Excel.
 * Layout template:
 *   Row 1: Judul "TEMPLATE IMPORT USER"
 *   Row 2: Instruksi
 *   Row 3: Header kolom (jadi key array)
 *   Row 4+: Data user
 */
class UserImport implements ToArray, WithHeadingRow
{
    /**
     * Heading row = baris ke-3 (header kolom).
     * Data otomatis dimulai dari row 4 (auto-detect oleh Laravel Excel).
     */
    public function headingRow(): int
    {
        return 3;
    }

    public function array(array $array): array
    {
        return $array;
    }
}
