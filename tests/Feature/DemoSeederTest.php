<?php

namespace Tests\Feature;

use App\Enums\LoanStatus;
use App\Models\InspectionMedia;
use App\Models\Item;
use App\Models\ItemPhoto;
use App\Models\Loan;
use App\Models\User;
use Database\Seeders\ItemPhotoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Data demo memakai foto sementara (kamera Zenit 11): foto alat, kartu pelajar, dan bukti inspeksi. */
class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        $this->travelTo(Carbon::parse('2026-10-21 10:00:00'));
        $this->seed();
    }

    private function mime(string $disk, string $path): ?string
    {
        $info = getimagesizefromstring(Storage::disk($disk)->get($path));

        return $info['mime'] ?? null;
    }

    public function test_setiap_alat_punya_tiga_foto_dengan_satu_sampul(): void
    {
        $items = Item::all();
        $this->assertGreaterThanOrEqual(7, $items->count());

        foreach ($items as $item) {
            $photos = $item->photos()->get();
            $this->assertCount(3, $photos, $item->code);
            $this->assertSame(1, $photos->where('is_cover', true)->count(), $item->code);
            $this->assertTrue($photos->first()->is_cover, 'foto pertama adalah sampul');
            $this->assertSame([1, 2, 3], $photos->pluck('sort_order')->all());

            foreach ($photos as $p) {
                Storage::disk('public')->assertExists($p->path);
                $this->assertSame('image/jpeg', $this->mime('public', $p->path), $p->path);
            }
        }
    }

    public function test_sampul_bergantian_antar_alat(): void
    {
        $firstPaths = Item::orderBy('id')->get()->map(fn ($i) => md5(Storage::disk('public')->get($i->photos()->first()->path)));

        $this->assertGreaterThan(1, $firstPaths->unique()->count(), 'ada lebih dari satu variasi sampul');
    }

    public function test_katalog_dan_detail_menampilkan_foto_sementara(): void
    {
        $siswa = User::where('username', 'raka.pratama')->firstOrFail();
        $item = Item::where('code', 'ELK-012')->firstOrFail();

        $this->actingAs($siswa)->get(route('katalog.index'))->assertOk()->assertSee('storage/items/', false);

        $this->actingAs($siswa)->get(route('katalog.show', $item))->assertOk()
            ->assertSee('hoverZoom', false)
            ->assertSee('Klik untuk memperbesar')
            ->assertSee('demo-1.jpg', false)->assertSee('demo-2.jpg', false)->assertSee('demo-3.jpg', false)
            ->assertSee('open-viewer', false);
    }

    public function test_kartu_pelajar_demo_adalah_foto_jpeg(): void
    {
        $paths = Loan::pluck('student_card_path')->unique();

        $this->assertSame(['student-cards/demo-kartu.jpg'], $paths->values()->all());
        Storage::disk('local')->assertExists('student-cards/demo-kartu.jpg');
        $this->assertSame('image/jpeg', $this->mime('local', 'student-cards/demo-kartu.jpg'));
    }

    public function test_bukti_inspeksi_demo_ada_dan_bisa_dilihat_pemiliknya(): void
    {
        // 3 serah terima × 2 foto + 1 pengembalian × 1 foto
        $this->assertSame(7, InspectionMedia::count());
        foreach (InspectionMedia::all() as $m) {
            Storage::disk('local')->assertExists($m->path);
            $this->assertSame('image/jpeg', $this->mime('local', $m->path));
        }

        $borrowed = Loan::where('status', LoanStatus::Borrowed->value)->firstOrFail();
        $owner = $borrowed->user;
        $media = $borrowed->inspections()->where('type', 'handover')->firstOrFail()->media()->firstOrFail();

        $this->actingAs($owner)->get(route('pengajuan.show', $borrowed))->assertOk()
            ->assertSee('Foto kondisi alat')->assertSee('Saat diserahkan')->assertSee(route('berkas.media', $media), false);
        $this->actingAs($owner)->get(route('berkas.media', $media))->assertOk();
        $this->actingAs(User::where('username', 'nadia.putri')->first())->get(route('berkas.media', $media))->assertForbidden();
    }

    public function test_layar_pengembalian_demo_memiliki_foto_pembanding(): void
    {
        $admin = User::where('username', 'maya.sari')->firstOrFail();
        $borrowed = Loan::where('status', LoanStatus::Borrowed->value)->firstOrFail();

        $this->actingAs($admin)->get(route('admin.pengembalian.show', $borrowed))->assertOk()
            ->assertSee('Sebelum (serah terima)')->assertSee('Perbesar bukti serah terima 1')->assertDontSee('Belum ada foto serah terima.');
    }

    public function test_siswa_demo_punya_email_untuk_lupa_password(): void
    {
        $this->assertSame('raka.pratama@siswa.sman-nusantara.sch.id', User::where('username', 'raka.pratama')->value('email'));
        $this->assertSame(6, User::siswa()->whereNotNull('email')->count());
    }

    public function test_seeder_idempoten_tidak_menggandakan_foto_atau_pengajuan(): void
    {
        $photos = ItemPhoto::count();
        $loans = Loan::count();
        $media = InspectionMedia::count();

        $this->seed();

        $this->assertSame($photos, ItemPhoto::count());
        $this->assertSame($loans, Loan::count());
        $this->assertSame($media, InspectionMedia::count());
    }

    public function test_foto_yang_sudah_diunggah_admin_tidak_ditimpa_seeder(): void
    {
        $item = Item::factory()->create();
        ItemPhoto::create(['item_id' => $item->id, 'path' => 'items/custom.jpg', 'sort_order' => 1, 'is_cover' => true]);

        $this->seed(ItemPhotoSeeder::class);

        $this->assertSame(['items/custom.jpg'], $item->photos()->pluck('path')->all());
    }
}
