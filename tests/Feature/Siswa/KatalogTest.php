<?php

namespace Tests\Feature\Siswa;

use App\Enums\LoanStatus;
use App\Models\Category;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class KatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $siswa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-19 09:00:00'));
        $this->siswa = User::factory()->create();
    }

    public function test_menampilkan_alat_aktif_dan_menyembunyikan_nonaktif(): void
    {
        Item::factory()->withUnits(2)->create(['name' => 'Kamera Uji']);
        Item::factory()->inactive()->withUnits(1)->create(['name' => 'Alat Nonaktif']);

        $this->actingAs($this->siswa)->get(route('katalog.index'))
            ->assertOk()
            ->assertSee('Kamera Uji')
            ->assertDontSee('Alat Nonaktif')
            ->assertSee('unit siap dipinjam hari ini');
    }

    public function test_pencarian_nama_dan_kode(): void
    {
        Item::factory()->withUnits(1)->create(['name' => 'Proyektor Uji', 'code' => 'AVS-777']);
        Item::factory()->withUnits(1)->create(['name' => 'Mikroskop Uji', 'code' => 'LAB-888']);

        $this->actingAs($this->siswa)->get(route('katalog.index', ['q' => 'proyektor']))
            ->assertSee('Proyektor Uji')->assertDontSee('Mikroskop Uji');
        $this->actingAs($this->siswa)->get(route('katalog.index', ['q' => 'LAB-888']))
            ->assertSee('Mikroskop Uji')->assertDontSee('Proyektor Uji');
    }

    public function test_filter_kategori(): void
    {
        $a = Category::factory()->create(['slug' => 'kat-a', 'name' => 'Kategori A']);
        $b = Category::factory()->create(['slug' => 'kat-b', 'name' => 'Kategori B']);
        Item::factory()->withUnits(1)->create(['category_id' => $a->id, 'name' => 'Item Dalam A']);
        Item::factory()->withUnits(1)->create(['category_id' => $b->id, 'name' => 'Item Dalam B']);

        $this->actingAs($this->siswa)->get(route('katalog.index', ['kategori' => 'kat-a']))
            ->assertSee('Item Dalam A')->assertDontSee('Item Dalam B');
    }

    public function test_filter_hanya_yang_tersedia_menyembunyikan_alat_yang_stoknya_dipesan_hari_ini(): void
    {
        $penuh = Item::factory()->withUnits(2)->create(['name' => 'Alat Penuh']);
        Item::factory()->withUnits(2)->create(['name' => 'Alat Lega']);
        Loan::factory()->between('2026-10-19', '2026-10-20')->create(['item_id' => $penuh->id, 'quantity' => 2]);

        $this->actingAs($this->siswa)->get(route('katalog.index'))
            ->assertSee('Alat Penuh')->assertSee('Alat Lega');
        $this->actingAs($this->siswa)->get(route('katalog.index', ['tersedia' => 1]))
            ->assertDontSee('Alat Penuh')->assertSee('Alat Lega');
    }

    public function test_urut_nama(): void
    {
        Item::factory()->withUnits(1)->create(['name' => 'Zebra Alat']);
        Item::factory()->withUnits(1)->create(['name' => 'Alpha Alat']);

        $this->actingAs($this->siswa)->get(route('katalog.index', ['urut' => 'nama']))
            ->assertSeeInOrder(['Alpha Alat', 'Zebra Alat']);
    }

    public function test_keadaan_kosong_menawarkan_reset_filter(): void
    {
        $this->actingAs($this->siswa)->get(route('katalog.index', ['q' => 'tidak-ada-alat-ini']))
            ->assertOk()->assertSee('Tidak ada alat yang cocok')->assertSee('Reset filter');
    }

    public function test_detail_menampilkan_info_dan_form(): void
    {
        $item = Item::factory()->withUnits(3)->create([
            'name' => 'Kamera Detail', 'specs' => ['Sensor' => 'APS-C'], 'max_loan_days' => 5,
        ]);

        $this->actingAs($this->siswa)->get(route('katalog.show', $item))
            ->assertOk()
            ->assertSee('Kamera Detail')
            ->assertSee('Spesifikasi')
            ->assertSee('APS-C')
            ->assertSee('Pinjam dengan bertanggung jawab')
            ->assertSee('Ajukan Peminjaman')
            ->assertSee('Maksimal 5 hari');
    }

    public function test_peringatan_keterlambatan_pada_ambang_batas(): void
    {
        $item = Item::factory()->withUnits(1)->create();
        $this->siswa->update(['late_count' => 3]);

        $this->actingAs($this->siswa)->get(route('katalog.show', $item))->assertSee('Riwayat keterlambatan');
    }

    public function test_riwayat_menyaring_berdasarkan_status_dan_hanya_milik_sendiri(): void
    {
        $item = Item::factory()->withUnits(1)->create();
        Loan::factory()->status(LoanStatus::Returned)->create(['user_id' => $this->siswa->id, 'item_id' => $item->id, 'request_code' => 'REQ-2026-9001']);
        Loan::factory()->create(['user_id' => $this->siswa->id, 'item_id' => $item->id, 'request_code' => 'REQ-2026-9002']);
        Loan::factory()->create(['item_id' => $item->id, 'request_code' => 'REQ-2026-9003']); // milik siswa lain

        $this->actingAs($this->siswa)->get(route('riwayat.index'))
            ->assertSee('REQ-2026-9001')->assertSee('REQ-2026-9002')->assertDontSee('REQ-2026-9003');
        $this->actingAs($this->siswa)->get(route('riwayat.index', ['status' => 'returned']))
            ->assertSee('REQ-2026-9001')->assertDontSee('REQ-2026-9002');
    }
}
