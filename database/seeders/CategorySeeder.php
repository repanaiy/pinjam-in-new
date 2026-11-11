<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // 5 kategori awal dari database_pinjam_alat.sql + 3 tambahan (total 8 sesuai spesifikasi).
        $rows = [
            ['Elektronik', 'elektronik', 'ELK'],
            ['Laboratorium', 'laboratorium', 'LAB'],
            ['Audio Visual', 'audio-visual', 'AVS'],
            ['Olahraga', 'olahraga', 'OLR'],
            ['Praktikum', 'praktikum', 'PRK'],
            ['Multimedia', 'multimedia', 'MLT'],
            ['Robotika', 'robotika', 'ROB'],
            ['Perlengkapan Kegiatan', 'perlengkapan-kegiatan', 'KGT'],
        ];

        foreach ($rows as $i => [$name, $slug, $prefix]) {
            Category::query()->updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'code_prefix' => $prefix,
                'sort_order' => $i + 1,
            ]);
        }
    }
}
