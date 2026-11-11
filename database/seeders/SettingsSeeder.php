<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['service_days', '1,2,3,4,5', 'Hari layanan (1=Senin ... 7=Minggu)'],
            ['service_hour_start', '07:00', 'Jam buka layanan'],
            ['service_hour_end', '15:00', 'Jam tutup layanan'],
            ['max_loan_days', '5', 'Maksimal hari peminjaman default'],
            ['review_sla_workdays', '1', 'Batas hari kerja peninjauan admin'],
            ['pickup_grace_workdays', '1', 'Toleransi hari kerja pengambilan sebelum expired'],
            ['late_warning_threshold', '3', 'Jumlah keterlambatan sebelum siswa diberi peringatan'],
            ['academic_year', '2026/2027', 'Tahun ajaran'],
            ['semester_label', 'Semester ganjil 2026', 'Label semester'],
        ];

        foreach ($rows as [$key, $value, $desc]) {
            Setting::query()->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'description' => $desc, 'updated_at' => now()]
            );
        }
    }
}
