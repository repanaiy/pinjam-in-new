<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/** Pembaca sheet XLSX/XLS untuk import siswa (CSV dibaca langsung oleh StudentImportService). */
class StudentsSheet implements ToArray, WithHeadingRow
{
    public function array(array $array): void
    {
        // Hanya dipakai lewat Excel::toArray(); pemrosesan ada di StudentImportService.
    }
}
