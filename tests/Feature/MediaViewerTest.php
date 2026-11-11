<?php

namespace Tests\Feature;

use App\Enums\LoanStatus;
use App\Models\InspectionMedia;
use App\Models\Item;
use App\Models\ItemPhoto;
use App\Models\Loan;
use App\Models\LoanInspection;
use App\Models\LoanUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Penampil gambar (zoom/geser/galeri) dan pemutar video terpasang di halaman yang tepat. */
class MediaViewerTest extends TestCase
{
    use RefreshDatabase;

    private User $siswa;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-21 10:00:00'));
        $this->siswa = User::factory()->create();
        $this->admin = User::factory()->admin()->create();
    }

    private function item(array $o = [], int $photos = 2): Item
    {
        $item = Item::factory()->withUnits(2)->create($o);
        for ($i = 1; $i <= $photos; $i++) {
            ItemPhoto::create(['item_id' => $item->id, 'path' => "items/{$item->id}/f{$i}.jpg", 'sort_order' => $i, 'is_cover' => $i === 1]);
        }

        return $item;
    }

    public function test_penampil_terpasang_di_layout_siswa_dan_admin(): void
    {
        foreach ([[$this->siswa, 'katalog.index'], [$this->admin, 'admin.dashboard']] as [$user, $route]) {
            $this->actingAs($user)->get(route($route))->assertOk()
                ->assertSee('Penampil gambar')->assertSee('Gulir atau cubit untuk zoom')->assertSee('imageViewer()', false);
        }
    }

    public function test_penampil_tidak_ada_di_halaman_login(): void
    {
        $this->get(route('login'))->assertOk()->assertDontSee('Penampil gambar');
    }

    public function test_detail_alat_memiliki_galeri_zoom_hover_dan_thumbnail(): void
    {
        $item = $this->item();

        $this->actingAs($this->siswa)->get(route('katalog.show', $item))->assertOk()
            ->assertSee('hoverZoom(2.2)', false)
            ->assertSee("\$dispatch('open-viewer'", false)
            ->assertSee('Klik untuk memperbesar')
            ->assertSee('f1.jpg', false)->assertSee('f2.jpg', false)
            ->assertSee('Pilih foto');
    }

    public function test_video_youtube_dimuat_setelah_diklik_dengan_mode_privasi(): void
    {
        $item = $this->item(['video_url' => 'https://youtu.be/dQw4w9WgXcQ', 'video_title' => 'Panduan Kamera', 'video_duration' => '04:30']);

        $this->actingAs($this->siswa)->get(route('katalog.show', $item))
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('x-data="{ play: false }"', false)
            ->assertSee('Putar video panduan')
            ->assertSee('Panduan Kamera')->assertSee('04:30');
    }

    public function test_video_vimeo_mp4_dan_tautan_biasa(): void
    {
        $vimeo = $this->item(['video_url' => 'https://vimeo.com/76979871']);
        $this->actingAs($this->siswa)->get(route('katalog.show', $vimeo))->assertSee('player.vimeo.com/video/76979871', false);

        $mp4 = $this->item(['video_url' => 'https://cdn.example.org/panduan.mp4']);
        $this->actingAs($this->siswa)->get(route('katalog.show', $mp4))
            ->assertSee('<video controls autoplay playsinline', false)->assertSee('https://cdn.example.org/panduan.mp4', false);

        $link = $this->item(['video_url' => 'https://example.org/panduan']);
        $this->actingAs($this->siswa)->get(route('katalog.show', $link))
            ->assertSee('Tonton')->assertDontSee('Putar video panduan')->assertDontSee('<iframe', false);
    }

    public function test_tanpa_video_atau_url_berbahaya_blok_video_tidak_tampil(): void
    {
        $none = $this->item(['video_url' => null]);
        $bad = $this->item(['video_url' => 'javascript:alert(1)']);

        foreach ([$none, $bad] as $item) {
            $this->actingAs($this->siswa)->get(route('katalog.show', $item))->assertOk()
                ->assertDontSee('Video panduan')->assertDontSee('javascript:alert', false);
        }
    }

    public function test_form_inventaris_foto_dapat_diperbesar(): void
    {
        $item = $this->item();

        $this->actingAs($this->admin)->get(route('admin.inventaris.edit', $item))->assertOk()
            ->assertSee('Perbesar foto 1')->assertSee('Perbesar foto 2')->assertSee("\$dispatch('open-viewer'", false);
    }

    public function test_verifikasi_kartu_pelajar_gambar_dapat_diperbesar(): void
    {
        $loan = Loan::factory()->create(['item_id' => $this->item()->id, 'user_id' => $this->siswa->id, 'student_card_path' => 'student-cards/a.jpg']);

        $this->actingAs($this->admin)->get(route('admin.verifikasi.show', $loan))->assertOk()
            ->assertSee('Perbesar kartu pelajar')->assertSee(route('berkas.kartu', $loan), false);
    }

    public function test_kartu_pelajar_pdf_tidak_dibuka_di_penampil_gambar(): void
    {
        $loan = Loan::factory()->create(['item_id' => $this->item()->id, 'user_id' => $this->siswa->id, 'student_card_path' => 'student-cards/a.pdf']);

        $this->actingAs($this->admin)->get(route('admin.verifikasi.show', $loan))->assertOk()
            ->assertDontSee('Perbesar kartu pelajar')->assertSee('Buka kartu pelajar (PDF)');
        $this->actingAs($this->siswa)->get(route('pengajuan.show', $loan))->assertOk()
            ->assertSee('Buka kartu pelajar (PDF)')->assertDontSee('Lihat kartu pelajar');
    }

    public function test_siswa_dapat_memperbesar_kartu_pelajar_gambar_miliknya(): void
    {
        $loan = Loan::factory()->create(['item_id' => $this->item()->id, 'user_id' => $this->siswa->id, 'student_card_path' => 'student-cards/a.png']);

        $this->actingAs($this->siswa)->get(route('pengajuan.show', $loan))->assertOk()
            ->assertSee('Lihat kartu pelajar')->assertSee('@click.prevent', false)->assertDontSee('Buka kartu pelajar (PDF)');
    }

    public function test_halaman_serah_terima_kartu_dapat_diperbesar_dan_ada_pratinjau_bukti(): void
    {
        $item = $this->item(['checklist_template' => ['Lengkap']]);
        $loan = Loan::factory()->status(LoanStatus::ReadyPickup)->create(['item_id' => $item->id, 'user_id' => $this->siswa->id, 'student_card_path' => 'student-cards/a.jpg']);

        $this->actingAs($this->admin)->get(route('admin.verifikasi.handover.create', $loan))->assertOk()
            ->assertSee('Perbesar kartu pelajar')->assertSee('Pratinjau bukti')->assertSee('previews', false);
    }

    public function test_foto_kondisi_tampil_di_halaman_lacak_siswa_hanya_bila_ada(): void
    {
        $item = $this->item();
        $loan = Loan::factory()->status(LoanStatus::Borrowed)->create(['item_id' => $item->id, 'user_id' => $this->siswa->id]);

        $this->actingAs($this->siswa)->get(route('pengajuan.show', $loan))->assertOk()->assertDontSee('Foto kondisi alat');

        $insp = LoanInspection::create(['loan_id' => $loan->id, 'type' => 'handover', 'checklist' => [], 'final_condition' => 'baik', 'inspected_by' => $this->admin->id, 'inspected_at' => now()]);
        $img = InspectionMedia::create(['inspection_id' => $insp->id, 'media_type' => 'photo', 'path' => 'x/1.jpg']);
        $vid = InspectionMedia::create(['inspection_id' => $insp->id, 'media_type' => 'video', 'path' => 'x/2.mp4']);

        $this->actingAs($this->siswa)->get(route('pengajuan.show', $loan->fresh()))->assertOk()
            ->assertSee('Foto kondisi alat')->assertSee('Saat diserahkan')
            ->assertSee(route('berkas.media', $img), false)            // <img src="..."> memuat URL polos
            ->assertSee('berkas\\\\\\/media\\\\\\/'.$vid->id, false)              // video hanya ada di JSON @js (slash di-escape)
            ->assertSee('\u0022type\u0022:\u0022video\u0022', false);   // @js meng-encode tanda kutip sebagai \u0022
    }

    public function test_layar_pengembalian_menampilkan_bukti_sebelum_sebagai_tombol_zoom(): void
    {
        $item = $this->item();
        $loan = Loan::factory()->status(LoanStatus::Borrowed)->between('2026-10-19', '2026-10-23')->create(['item_id' => $item->id, 'user_id' => $this->siswa->id]);
        LoanUnit::create(['loan_id' => $loan->id, 'item_unit_id' => $item->units()->first()->id, 'condition_out' => 'baik']);
        $insp = LoanInspection::create(['loan_id' => $loan->id, 'type' => 'handover', 'checklist' => [], 'final_condition' => 'baik', 'inspected_by' => $this->admin->id, 'inspected_at' => now()]);
        InspectionMedia::create(['inspection_id' => $insp->id, 'media_type' => 'photo', 'path' => 'x/1.jpg']);
        InspectionMedia::create(['inspection_id' => $insp->id, 'media_type' => 'video', 'path' => 'x/2.mp4']);

        $this->actingAs($this->admin)->get(route('admin.pengembalian.show', $loan))->assertOk()
            ->assertSee('Perbesar bukti serah terima 1')->assertSee('Perbesar bukti serah terima 2')
            ->assertSee('previewItems', false);
    }
}
