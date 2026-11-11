<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\ItemPhoto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Menyalin foto katalog dari public/images ke disk public dan menautkannya ke alat (berdasarkan kode).
 * Aman dijalankan berulang saat start: foto yang filenya hilang diganti, foto unggahan admin yang filenya ada dibiarkan.
 */
class SyncCatalogPhotos extends Command
{
    protected $signature = 'catalog:sync-photos';

    protected $description = 'Salin foto katalog bawaan (public/images) ke storage dan tautkan ke alat';

    private const MAP = [
        'ELK-012' => 'kamera dslr.jpg',
        'AVS-004' => 'proyektor epson.jpg',
        'LAB-007' => 'mikroskop.jpg',
        'AVS-009' => 'tripod.jpg',
        'AVS-015' => 'speaker portabel.jpg',
        'OLR-001' => 'bola basket molten.jpg',
    ];

    public function handle(): int
    {
        $disk = Storage::disk('public');

        foreach (self::MAP as $code => $file) {
            $item = Item::query()->where('code', $code)->first();
            $src = public_path('images/'.$file);
            if (! $item || ! File::exists($src)) {
                continue;
            }

            // Buang rujukan foto yang filenya tidak ada di disk.
            $item->photos()->get()->each(fn (ItemPhoto $p) => $disk->exists($p->path) ?: $p->delete());

            if ($item->photos()->exists()) {
                continue;
            }

            $path = "items/{$item->id}/cover.jpg";
            $disk->put($path, File::get($src));
            ItemPhoto::create(['item_id' => $item->id, 'path' => $path, 'sort_order' => 1, 'is_cover' => true]);
            $this->info("{$item->name} <- {$file}");
        }

        return self::SUCCESS;
    }
}
