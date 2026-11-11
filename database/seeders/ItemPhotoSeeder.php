<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ItemPhoto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Foto sementara untuk seluruh alat demo: satu foto kamera Zenit 11 (utuh + potongan lensa, bodi, tripod)
 * disalin ke disk public sebagai 3 foto per alat; sampul bergantian agar katalog tidak seragam.
 * Ganti dengan foto asli lewat menu Inventaris → Ubah → Foto alat.
 */
class ItemPhotoSeeder extends Seeder
{
    public const ASSETS = 'seeders/assets/demo';

    public function run(): void
    {
        $dir = database_path(self::ASSETS);
        $sets = [
            ['zenit-utuh.jpg', 'zenit-lensa.jpg', 'zenit-bodi.jpg'],
            ['zenit-bodi.jpg', 'zenit-utuh.jpg', 'zenit-tripod.jpg'],
        ];

        Item::query()->orderBy('id')->get()->each(function (Item $item, int $n) use ($dir, $sets) {
            if ($item->photos()->exists()) {
                return; // jangan menimpa foto yang sudah diunggah admin
            }

            foreach ($sets[$n % 2] as $i => $file) {
                $path = "items/{$item->id}/demo-".($i + 1).'.jpg';
                Storage::disk('public')->put($path, File::get("{$dir}/{$file}"));

                ItemPhoto::create([
                    'item_id' => $item->id,
                    'path' => $path,
                    'sort_order' => $i + 1,
                    'is_cover' => $i === 0,
                ]);
            }
        });
    }
}
