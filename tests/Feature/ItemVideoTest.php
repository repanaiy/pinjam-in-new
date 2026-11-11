<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ItemVideoTest extends TestCase
{
    use RefreshDatabase;

    private const YT = 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0&modestbranding=1';

    public static function urls(): array
    {
        return [
            'youtube watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'youtube', self::YT],
            'youtu.be dengan waktu' => ['https://youtu.be/dQw4w9WgXcQ?t=42', 'youtube', self::YT],
            'youtube parameter lain' => ['https://www.youtube.com/watch?feature=share&v=dQw4w9WgXcQ&t=10', 'youtube', self::YT],
            'youtube embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', self::YT],
            'youtube shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ', 'youtube', self::YT],
            'vimeo' => ['https://vimeo.com/76979871', 'vimeo', 'https://player.vimeo.com/video/76979871'],
            'vimeo player' => ['https://player.vimeo.com/video/76979871', 'vimeo', 'https://player.vimeo.com/video/76979871'],
            'mp4 dengan query' => ['https://cdn.example.org/panduan.mp4?x=1', 'file', 'https://cdn.example.org/panduan.mp4?x=1'],
            'webm' => ['https://cdn.example.org/a.webm', 'file', 'https://cdn.example.org/a.webm'],
            'tautan biasa' => ['https://example.org/panduan', 'link', null],
            'id youtube tidak valid' => ['https://youtube.com/watch?v=pendek', 'link', null],
            'skema berbahaya' => ['javascript:alert(1)', null, null],
            'skema ftp' => ['ftp://x.test/a.mp4', null, null],
            'kosong' => ['', null, null],
            'null' => [null, null, null],
        ];
    }

    #[DataProvider('urls')]
    public function test_jenis_dan_url_pemutar_video(?string $url, ?string $type, ?string $embed): void
    {
        $item = new Item(['video_url' => $url]);

        $this->assertSame($type, $item->videoType());
        $this->assertSame($embed, $item->videoEmbedUrl());
        $this->assertSame($type === null ? null : trim((string) $url), $item->videoLink());
    }

    public function test_item_gallery_items_berisi_src_alt_caption_dan_tipe(): void
    {
        $item = Item::factory()->create(['name' => 'Kamera Galeri']);
        foreach ([1, 2, 3] as $n) {
            ItemPhoto::create(['item_id' => $item->id, 'path' => "items/{$item->id}/f{$n}.jpg", 'sort_order' => $n, 'is_cover' => $n === 1]);
        }

        $g = $item->fresh()->galleryItems();

        $this->assertCount(3, $g);
        $this->assertSame(['src', 'alt', 'caption', 'type'], array_keys($g[0]));
        $this->assertStringEndsWith("/storage/items/{$item->id}/f1.jpg", $g[0]['src']);
        $this->assertSame('Kamera Galeri', $g[0]['alt']);
        $this->assertSame('Kamera Galeri — foto 2 dari 3', $g[1]['caption']);
        $this->assertSame('image', $g[2]['type']);
    }

    public function test_satu_foto_memakai_nama_alat_sebagai_keterangan(): void
    {
        $item = Item::factory()->create(['name' => 'Alat Tunggal']);
        ItemPhoto::create(['item_id' => $item->id, 'path' => 'items/x.jpg', 'sort_order' => 1, 'is_cover' => true]);

        $this->assertSame('Alat Tunggal', $item->fresh()->galleryItems()[0]['caption']);
        $this->assertSame([], Item::factory()->create()->galleryItems());
    }
}
