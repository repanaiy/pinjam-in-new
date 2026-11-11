<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HolidaySeeder extends Seeder
{
    /**
     * Berdasarkan SKB 3 Menteri No. 1497/2025, 2/2025, 5/2025: September–November 2026
     * tidak memiliki libur nasional maupun cuti bersama (Oktober 2026 hanya akhir pekan).
     * Libur khusus sekolah ditambahkan admin lewat Pengaturan (Fase 5).
     */
    public function run(): void
    {
        $rows = [
            ['2026-12-24', 'Cuti Bersama Natal'],
            ['2026-12-25', 'Hari Raya Natal'],
        ];

        foreach ($rows as [$date, $name]) {
            DB::table('holidays')->updateOrInsert(
                ['date' => $date],
                ['name' => $name, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
