<?php

namespace Tests\Feature\Admin;

use App\Enums\LoanStatus;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemPhoto;
use App\Models\ItemUnit;
use App\Models\Loan;
use App\Models\LoanUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InventarisTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private User $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->travelTo(Carbon::parse('2026-10-19 09:00:00'));

        $this->admin = User::factory()->admin()->create();
        $this->category = Category::factory()->create(['name' => 'Elektronik']);
    }

    private function png(string $name = 'foto.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(self::PNG));
    }

    private function payload(array $o = []): array
    {
        return array_merge([
            'category_id' => $this->category->id,
            'code' => 'elk-099',
            'name' => 'Kamera Baru',
            'summary' => '24 MP',
            'description' => 'Kamera untuk dokumentasi.',
            'specs' => [['key' => 'Sensor', 'value' => 'APS-C'], ['key' => '', 'value' => '']],
            'package_contents' => 'Body, lensa, charger',
            'checklist_text' => "Body lengkap\nCharger\n\n",
            'location' => 'Ruang Sarpras',
            'max_loan_days' => 7,
            'max_qty_per_loan' => 2,
            'min_stock' => 1,
            'status' => 'active',
            'video_url' => 'https://example.org/panduan',
            'video_title' => 'Panduan kamera',
            'video_duration' => '04:30',
            'initial_units' => 3,
            'photos' => [$this->png('a.png'), $this->png('b.png')],
        ], $o);
    }

    private function store(array $o = [])
    {
        return $this->actingAs($this->admin)->post(route('admin.inventaris.store'), $this->payload($o));
    }

    // ------------------------------------------------------------------ alat

    public function test_tambah_alat_membuat_item_unit_dan_foto(): void
    {
        $item = null;
        $this->store()->assertSessionHasNoErrors();
        $item = Item::where('code', 'ELK-099')->firstOrFail();

        $this->assertSame('Kamera Baru', $item->name);
        $this->assertSame(['Sensor' => 'APS-C'], $item->specs);
        $this->assertSame(['Body lengkap', 'Charger'], $item->checklist_template);
        $this->assertSame('04:30', $item->video_duration);

        $this->assertSame(['ELK-099-01', 'ELK-099-02', 'ELK-099-03'], $item->units()->orderBy('unit_code')->pluck('unit_code')->all());
        $this->assertSame(3, $item->units()->where('status', 'available')->count());

        $this->assertSame(2, $item->photos()->count());
        $this->assertSame(1, $item->photos()->where('is_cover', true)->count());
        foreach ($item->photos as $photo) {
            Storage::disk('public')->assertExists($photo->path);
        }
        $this->assertDatabaseHas('activity_logs', ['action' => 'item_created', 'actor_id' => $this->admin->id]);
    }

    public function test_alat_baru_muncul_di_katalog_siswa(): void
    {
        $this->store(['name' => 'Kamera Katalog']);

        $this->actingAs(User::factory()->create())->get(route('katalog.index'))
            ->assertOk()->assertSee('Kamera Katalog')->assertSee('3 unit');
    }

    public function test_nomor_unit_awal_melewati_kode_yang_sudah_dipakai(): void
    {
        $other = Item::factory()->create(['code' => 'ZZ-1']);
        ItemUnit::factory()->create(['item_id' => $other->id, 'unit_code' => 'ELK-099-01']); // bentrok dengan kode otomatis

        $this->store(['initial_units' => 2, 'photos' => []])->assertSessionHasNoErrors();

        $this->assertSame(['ELK-099-02', 'ELK-099-03'], Item::where('code', 'ELK-099')->first()->units()->orderBy('unit_code')->pluck('unit_code')->all());
    }

    public function test_validasi_alat(): void
    {
        Item::factory()->create(['code' => 'ELK-100']);
        $gone = Item::factory()->create(['code' => 'ELK-101']);
        $gone->delete();

        $this->store(['code' => 'ELK-100', 'photos' => []])->assertSessionHasErrors('code');
        $this->store(['code' => 'ELK-101', 'photos' => []])->assertSessionHasErrors('code'); // termasuk yang dihapus
        $this->store(['code' => 'kode salah!', 'photos' => []])->assertSessionHasErrors('code');
        $this->store(['category_id' => 9999, 'photos' => []])->assertSessionHasErrors('category_id');
        $this->store(['name' => '', 'photos' => []])->assertSessionHasErrors('name');
        $this->store(['max_loan_days' => 0, 'photos' => []])->assertSessionHasErrors('max_loan_days');
        $this->store(['max_loan_days' => 31, 'photos' => []])->assertSessionHasErrors('max_loan_days');
        $this->store(['max_qty_per_loan' => 0, 'photos' => []])->assertSessionHasErrors('max_qty_per_loan');
        $this->store(['min_stock' => -1, 'photos' => []])->assertSessionHasErrors('min_stock');
        $this->store(['status' => 'rusak', 'photos' => []])->assertSessionHasErrors('status');
        $this->store(['video_url' => 'bukan-url', 'photos' => []])->assertSessionHasErrors('video_url');
        $this->store(['video_duration' => '4.30', 'photos' => []])->assertSessionHasErrors('video_duration');
        $this->store(['photos' => [UploadedFile::fake()->create('bukan.pdf', 10, 'application/pdf')]])->assertSessionHasErrors('photos.0');
        $this->store(['photos' => [UploadedFile::fake()->create('besar.png', 4000, 'image/png')]])->assertSessionHasErrors('photos.0');

        $this->assertSame(0, Item::where('name', 'Kamera Baru')->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_ubah_alat(): void
    {
        $item = Item::factory()->withUnits(2)->create(['code' => 'ELK-050', 'name' => 'Lama', 'category_id' => $this->category->id]);

        $this->actingAs($this->admin)->put(route('admin.inventaris.update', $item), $this->payload([
            'code' => 'ELK-050', 'name' => 'Nama Baru', 'min_stock' => 3, 'status' => 'inactive', 'photos' => [],
        ]))->assertRedirect(route('admin.inventaris.edit', $item))->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame('Nama Baru', $item->name);
        $this->assertSame(3, $item->min_stock);
        $this->assertSame('inactive', $item->status->value);
        $this->assertSame(2, $item->units()->count(), 'unit tidak berubah');
        $this->assertDatabaseHas('activity_logs', ['action' => 'item_updated']);
    }

    public function test_ubah_alat_tidak_boleh_memakai_kode_alat_lain(): void
    {
        Item::factory()->create(['code' => 'ELK-060']);
        $item = Item::factory()->create(['code' => 'ELK-061', 'category_id' => $this->category->id]);

        $this->actingAs($this->admin)->put(route('admin.inventaris.update', $item), $this->payload(['code' => 'ELK-060', 'photos' => []]))
            ->assertSessionHasErrors('code');
    }

    public function test_alat_nonaktif_tidak_tampil_di_katalog(): void
    {
        $item = Item::factory()->withUnits(1)->create(['name' => 'Alat Disembunyikan', 'category_id' => $this->category->id]);

        $this->actingAs($this->admin)->put(route('admin.inventaris.update', $item), $this->payload(['code' => $item->code, 'name' => 'Alat Disembunyikan', 'status' => 'inactive', 'photos' => []]));

        $this->actingAs(User::factory()->create())->get(route('katalog.index'))->assertDontSee('Alat Disembunyikan');
    }

    public function test_halaman_ubah_menampilkan_unit_dan_foto(): void
    {
        $item = Item::factory()->withUnits(2)->create(['category_id' => $this->category->id]);

        $this->actingAs($this->admin)->get(route('admin.inventaris.edit', $item))
            ->assertOk()->assertSee('Unit fisik (2)')->assertSee($item->code.'-01')->assertSee('Foto alat (0)');
        $this->actingAs($this->admin)->get(route('admin.inventaris.create'))->assertOk()->assertSee('Jumlah unit awal');
    }

    public function test_unit_per_alat_berpaginasi_sepuluh_per_halaman(): void
    {
        $item = Item::factory()->withUnits(12)->create(['code' => 'PAG-1', 'category_id' => $this->category->id]);

        $this->actingAs($this->admin)->get(route('admin.inventaris.edit', $item))->assertOk()
            ->assertSee('Unit fisik (12)')->assertSee('PAG-1-10')->assertDontSee('PAG-1-11')->assertSee('Berikutnya');

        $this->actingAs($this->admin)->get(route('admin.inventaris.edit', [$item, 'unit' => 2]))->assertOk()
            ->assertSee('PAG-1-11')->assertSee('PAG-1-12')->assertDontSee('PAG-1-01');
    }

    public function test_daftar_inventaris_cari_filter_dan_stok_minim(): void
    {
        $lain = Category::factory()->create();
        Item::factory()->withUnits(1)->create(['name' => 'Proyektor Uji', 'code' => 'AVS-777', 'min_stock' => 2, 'category_id' => $this->category->id]);
        Item::factory()->withUnits(5)->create(['name' => 'Mikroskop Uji', 'code' => 'LAB-888', 'min_stock' => 1, 'category_id' => $lain->id, 'status' => 'inactive']);

        $this->actingAs($this->admin);
        $this->get(route('admin.inventaris.index'))->assertOk()->assertSee('Proyektor Uji')->assertSee('Mikroskop Uji')->assertSee('Stok minim');
        $this->get(route('admin.inventaris.index', ['q' => 'LAB-888']))->assertSee('Mikroskop Uji')->assertDontSee('Proyektor Uji');
        $this->get(route('admin.inventaris.index', ['kategori' => $lain->id]))->assertSee('Mikroskop Uji')->assertDontSee('Proyektor Uji');
        $this->get(route('admin.inventaris.index', ['status' => 'inactive']))->assertSee('Mikroskop Uji')->assertDontSee('Proyektor Uji');
        $this->get(route('admin.inventaris.index', ['q' => 'tidak-ada']))->assertOk()->assertSee('Belum ada alat');
    }

    public function test_hapus_alat_adalah_soft_delete_dan_riwayat_tetap_utuh(): void
    {
        $item = Item::factory()->withUnits(2)->create(['name' => 'Alat Riwayat', 'category_id' => $this->category->id]);
        $siswa = User::factory()->create();
        $loan = Loan::factory()->status(LoanStatus::Returned)->create(['user_id' => $siswa->id, 'item_id' => $item->id]);

        $this->actingAs($this->admin)->delete(route('admin.inventaris.destroy', $item))
            ->assertRedirect(route('admin.inventaris.index'));

        $this->assertSoftDeleted('items', ['id' => $item->id]);
        $this->assertSame(2, ItemUnit::withTrashed()->where('item_id', $item->id)->whereNotNull('deleted_at')->count(), 'unit ikut soft delete');
        $this->assertDatabaseHas('loans', ['id' => $loan->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'item_deleted']);

        $this->actingAs($siswa)->get(route('katalog.index'))->assertDontSee('Alat Riwayat');
        $this->actingAs($siswa)->get(route('katalog.show', $item->id))->assertNotFound();
        $this->actingAs($siswa)->get(route('pengajuan.show', $loan))->assertOk()->assertSee('Alat Riwayat');
    }

    public function test_hapus_alat_ditolak_bila_masih_ada_pengajuan_atau_peminjaman_aktif(): void
    {
        $item = Item::factory()->withUnits(2)->create(['category_id' => $this->category->id]);

        foreach ([LoanStatus::Pending, LoanStatus::Approved, LoanStatus::Borrowed, LoanStatus::Overdue] as $status) {
            $loan = Loan::factory()->status($status)->create(['item_id' => $item->id]);

            $this->actingAs($this->admin)->delete(route('admin.inventaris.destroy', $item))
                ->assertSessionHas('toast.title', 'Tidak bisa dihapus');
            $this->assertNotSoftDeleted('items', ['id' => $item->id]);

            $loan->forceDelete();
        }
    }

    public function test_hanya_admin_yang_boleh_mengelola_inventaris(): void
    {
        $siswa = User::factory()->create();
        $item = Item::factory()->create(['category_id' => $this->category->id]);

        $this->actingAs($siswa)->get(route('admin.inventaris.index'))->assertForbidden();
        $this->actingAs($siswa)->get(route('admin.inventaris.create'))->assertForbidden();
        $this->actingAs($siswa)->post(route('admin.inventaris.store'), $this->payload())->assertForbidden();
        $this->actingAs($siswa)->delete(route('admin.inventaris.destroy', $item))->assertForbidden();
        $this->assertNotSoftDeleted('items', ['id' => $item->id]);
    }

    // ------------------------------------------------------------------ unit

    public function test_tambah_ubah_dan_hapus_unit(): void
    {
        $item = Item::factory()->create(['code' => 'ELK-070', 'category_id' => $this->category->id]);
        $this->actingAs($this->admin);

        $this->post(route('admin.inventaris.units.store', $item), ['unit_code' => 'elk-070-01', 'status' => 'available', 'notes' => 'baru'])
            ->assertSessionHasNoErrors();
        $unit = ItemUnit::where('unit_code', 'ELK-070-01')->firstOrFail();
        $this->assertSame($item->id, $unit->item_id);

        $this->patch(route('admin.inventaris.units.update', $unit), ['unit_code' => 'ELK-070-01', 'status' => 'maintenance', 'notes' => 'lensa kotor'])
            ->assertSessionHasNoErrors();
        $this->assertSame('maintenance', $unit->fresh()->status->value);
        $this->assertSame('lensa kotor', $unit->fresh()->notes);

        $this->delete(route('admin.inventaris.units.destroy', $unit))->assertSessionHas('toast.title', 'Unit dihapus');
        $this->assertSoftDeleted('item_units', ['id' => $unit->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'unit_deleted']);
    }

    public function test_kode_unit_harus_unik_di_seluruh_alat_termasuk_yang_dihapus(): void
    {
        $a = Item::factory()->withUnits(1)->create(['code' => 'ELK-071', 'category_id' => $this->category->id]);
        $b = Item::factory()->create(['code' => 'ELK-072', 'category_id' => $this->category->id]);
        $deleted = ItemUnit::factory()->create(['item_id' => $b->id, 'unit_code' => 'ELK-072-09']);
        $deleted->delete();

        $this->actingAs($this->admin)->post(route('admin.inventaris.units.store', $b), ['unit_code' => 'ELK-071-01', 'status' => 'available'])
            ->assertSessionHasErrors('unit_code', null, 'unit');
        $this->actingAs($this->admin)->post(route('admin.inventaris.units.store', $b), ['unit_code' => 'ELK-072-09', 'status' => 'available'])
            ->assertSessionHasErrors('unit_code', null, 'unit');
        $this->actingAs($this->admin)->post(route('admin.inventaris.units.store', $b), ['unit_code' => 'kode salah!', 'status' => 'available'])
            ->assertSessionHasErrors('unit_code', null, 'unit');
        $this->actingAs($this->admin)->post(route('admin.inventaris.units.store', $b), ['unit_code' => 'ELK-072-01', 'status' => 'dipinjam'])
            ->assertSessionHasErrors('status', null, 'unit');
    }

    public function test_unit_yang_sedang_dipinjam_tidak_bisa_diubah_statusnya_atau_dihapus(): void
    {
        $item = Item::factory()->withUnits(2)->create(['category_id' => $this->category->id]);
        $unit = $item->units()->orderBy('id')->first();
        $loan = Loan::factory()->status(LoanStatus::Borrowed)->create(['item_id' => $item->id]);
        LoanUnit::create(['loan_id' => $loan->id, 'item_unit_id' => $unit->id, 'condition_out' => 'baik']);

        $this->actingAs($this->admin);
        $this->patch(route('admin.inventaris.units.update', $unit), ['unit_code' => $unit->unit_code, 'status' => 'maintenance'])
            ->assertSessionHas('toast.title', 'Unit tidak diubah');
        $this->assertSame('available', $unit->fresh()->status->value);

        // Catatan boleh diubah selama status tetap.
        $this->patch(route('admin.inventaris.units.update', $unit), ['unit_code' => $unit->unit_code, 'status' => 'available', 'notes' => 'dibawa Raka'])
            ->assertSessionHas('toast.title', 'Unit diperbarui');
        $this->assertSame('dibawa Raka', $unit->fresh()->notes);

        $this->delete(route('admin.inventaris.units.destroy', $unit))->assertSessionHas('toast.title', 'Tidak bisa dihapus');
        $this->assertNotSoftDeleted('item_units', ['id' => $unit->id]);
    }

    // ------------------------------------------------------------------ foto

    public function test_foto_pertama_menjadi_sampul_ganti_sampul_dan_hapus(): void
    {
        $item = Item::factory()->create(['category_id' => $this->category->id]);
        $this->actingAs($this->admin);

        $this->post(route('admin.inventaris.photos.store', $item), ['photos' => [$this->png('1.png'), $this->png('2.png')]])->assertSessionHasNoErrors();
        [$first, $second] = $item->photos()->get()->all();

        $this->assertTrue($first->is_cover);
        $this->assertFalse($second->is_cover);
        $this->assertSame(asset('storage/'.$first->path), $item->fresh()->coverUrl());

        $this->post(route('admin.inventaris.photos.cover', $second))->assertSessionHasNoErrors();
        $this->assertFalse($first->fresh()->is_cover);
        $this->assertTrue($second->fresh()->is_cover);
        $this->assertSame(1, $item->photos()->where('is_cover', true)->count());

        // Menghapus sampul: berkas hilang dan foto lain menjadi sampul.
        $this->delete(route('admin.inventaris.photos.destroy', $second))->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($second->path);
        $this->assertTrue($first->fresh()->is_cover);
        $this->assertSame(1, $item->photos()->count());
    }

    public function test_unggah_foto_tidak_valid_ditolak(): void
    {
        $item = Item::factory()->create(['category_id' => $this->category->id]);

        $this->actingAs($this->admin)->post(route('admin.inventaris.photos.store', $item), ['photos' => [UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]])
            ->assertSessionHasErrors('photos.0', null, 'photos');
        $this->actingAs($this->admin)->post(route('admin.inventaris.photos.store', $item), [])
            ->assertSessionHasErrors('photos', null, 'photos');
        $this->assertSame(0, ItemPhoto::count());
    }

    public function test_sampul_tampil_di_kartu_katalog(): void
    {
        $item = Item::factory()->withUnits(1)->create(['category_id' => $this->category->id]);
        $this->actingAs($this->admin)->post(route('admin.inventaris.photos.store', $item), ['photos' => [$this->png()]]);
        $photo = $item->photos()->first();

        $this->actingAs(User::factory()->create())->get(route('katalog.index'))->assertSee('storage/'.$photo->path, false);
    }

    // ------------------------------------------------------------------ kategori

    public function test_kategori_dibuat_dengan_slug_unik_dan_nama_harus_unik(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('admin.inventaris.kategori.store'), ['name' => 'Audio Visual', 'code_prefix' => 'avs', 'sort_order' => 2])->assertSessionHasNoErrors();
        $c = Category::where('name', 'Audio Visual')->firstOrFail();
        $this->assertSame('audio-visual', $c->slug);
        $this->assertSame('AVS', $c->code_prefix);

        $this->post(route('admin.inventaris.kategori.store'), ['name' => 'Audio Visual'])->assertSessionHasErrors('name');

        $this->post(route('admin.inventaris.kategori.store'), ['name' => 'Audio-Visual'])->assertSessionHasNoErrors();
        $this->assertSame('audio-visual-2', Category::where('name', 'Audio-Visual')->value('slug'));
    }

    public function test_ubah_kategori_mempertahankan_slug(): void
    {
        $c = Category::factory()->create(['name' => 'Lama', 'slug' => 'lama']);

        $this->actingAs($this->admin)->patch(route('admin.inventaris.kategori.update', $c), ['name' => 'Baru', 'code_prefix' => 'BRU', 'sort_order' => 5])
            ->assertSessionHasNoErrors();

        $this->assertSame('Baru', $c->fresh()->name);
        $this->assertSame('lama', $c->fresh()->slug);
        $this->assertSame(5, $c->fresh()->sort_order);
    }

    public function test_hapus_kategori_ditolak_bila_dipakai_termasuk_alat_yang_dihapus(): void
    {
        $c = Category::factory()->create();
        $item = Item::factory()->create(['category_id' => $c->id]);
        $item->delete();

        $this->actingAs($this->admin)->delete(route('admin.inventaris.kategori.destroy', $c))
            ->assertSessionHas('toast.title', 'Tidak bisa dihapus');
        $this->assertDatabaseHas('categories', ['id' => $c->id]);

        $kosong = Category::factory()->create();
        $this->actingAs($this->admin)->delete(route('admin.inventaris.kategori.destroy', $kosong))
            ->assertSessionHas('toast.title', 'Kategori dihapus');
        $this->assertDatabaseMissing('categories', ['id' => $kosong->id]);
    }

    public function test_kategori_berpaginasi_lima_belas_per_halaman(): void
    {
        foreach (range(1, 16) as $i) {
            Category::factory()->create(['name' => sprintf('Kat %02d', $i), 'slug' => sprintf('kat-%02d', $i)]);
        }

        $page1 = $this->actingAs($this->admin)->get(route('admin.inventaris.kategori.index'))->assertOk()->viewData('categories');
        $this->assertSame(17, $page1->total()); // 16 + 'Elektronik' dari setUp
        $this->assertSame(15, $page1->count());

        $this->actingAs($this->admin)->get(route('admin.inventaris.kategori.index', ['page' => 2]))->assertOk()
            ->assertSee('Kat 15')->assertSee('Kat 16')->assertDontSee('Kat 01');
    }

    public function test_halaman_kategori(): void
    {
        $this->actingAs($this->admin)->get(route('admin.inventaris.kategori.index'))->assertOk()->assertSee('Elektronik');
        $this->actingAs(User::factory()->create())->get(route('admin.inventaris.kategori.index'))->assertForbidden();
    }
}
