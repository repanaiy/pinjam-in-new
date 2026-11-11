<?php

namespace Tests\Feature\Admin;

use App\Enums\LoanStatus;
use App\Models\InspectionMedia;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Loan;
use App\Models\LoanUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HandoverTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $siswa;

    private Item $item;

    private Loan $loan;

    /** @var Collection<int,ItemUnit> */
    private $units;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo(Carbon::parse('2026-10-21 09:00:00'));

        $this->admin = User::factory()->admin()->create();
        $this->siswa = User::factory()->create();
        $this->item = Item::factory()->withUnits(4)->create([
            'name' => 'Kamera Uji',
            'checklist_template' => ['Body & label sesuai', 'Baterai + charger'],
        ]);
        $this->units = $this->item->units()->orderBy('id')->get();
        $this->loan = Loan::factory()->status(LoanStatus::ReadyPickup)->between('2026-10-21', '2026-10-23')->create([
            'user_id' => $this->siswa->id, 'item_id' => $this->item->id, 'quantity' => 2,
            'pickup_date' => '2026-10-21', 'pickup_location' => 'Ruang Sarpras',
        ]);
    }

    private function payload(array $o = []): array
    {
        return array_merge([
            'card_verified' => '1',
            'unit_ids' => [$this->units[0]->id, $this->units[1]->id],
            'checklist' => [0, 1],
            'notes' => 'Lengkap',
            'media' => [UploadedFile::fake()->create('foto.jpg', 100, 'image/jpeg')],
        ], $o);
    }

    private function handover(array $o = [], ?Loan $loan = null)
    {
        return $this->actingAs($this->admin)->post(route('admin.verifikasi.handover.store', $loan ?? $this->loan), $this->payload($o));
    }

    public function test_halaman_serah_terima_menampilkan_unit_dan_checklist(): void
    {
        $this->actingAs($this->admin)->get(route('admin.verifikasi.handover.create', $this->loan))
            ->assertOk()
            ->assertSee($this->units[0]->unit_code)
            ->assertSee('Body &amp; label sesuai', false)
            ->assertSee('Konfirmasi Serah Terima')
            ->assertSee('Verifikasi kartu pelajar asli');
    }

    public function test_serah_terima_berhasil(): void
    {
        $this->handover()->assertRedirect(route('admin.verifikasi.show', $this->loan))->assertSessionHasNoErrors();

        $loan = $this->loan->fresh();
        $this->assertSame(LoanStatus::Borrowed, $loan->status);
        $this->assertNotNull($loan->borrowed_at);

        $this->assertSame(2, LoanUnit::where('loan_id', $loan->id)->count());
        $this->assertEqualsCanonicalizing(
            [$this->units[0]->id, $this->units[1]->id],
            LoanUnit::where('loan_id', $loan->id)->pluck('item_unit_id')->all()
        );

        $inspection = $loan->inspections()->where('type', 'handover')->firstOrFail();
        $this->assertSame($this->admin->id, $inspection->inspected_by);
        $this->assertSame('baik', $inspection->final_condition->value);
        $this->assertCount(2, $inspection->checklist);
        $this->assertTrue(collect($inspection->checklist)->every(fn ($c) => $c['checked'] === true));
        $this->assertSame(1, $inspection->media()->count());
        Storage::disk('local')->assertExists($inspection->media->first()->path);

        $this->assertDatabaseHas('activity_logs', ['loan_id' => $loan->id, 'action' => 'handover', 'actor_id' => $this->admin->id]);
        $this->assertSame('handover', $this->siswa->notifications()->first()->data['kind']);
    }

    public function test_unit_yang_diserahkan_tidak_lagi_menjadi_kandidat_tetapi_status_unit_tetap_available(): void
    {
        $this->handover();

        $this->assertSame(2, ItemUnit::where('item_id', $this->item->id)->lendable()->count());
        $this->assertSame(2, ItemUnit::where('item_id', $this->item->id)->inUse()->count());
        $this->assertSame(4, ItemUnit::where('item_id', $this->item->id)->where('status', 'available')->count());
    }

    public function test_jumlah_unit_harus_tepat(): void
    {
        $this->handover(['unit_ids' => [$this->units[0]->id]])->assertSessionHasErrors('unit_ids');
        $this->handover(['unit_ids' => [$this->units[0]->id, $this->units[1]->id, $this->units[2]->id]])->assertSessionHasErrors('unit_ids');

        $this->assertSame(LoanStatus::ReadyPickup, $this->loan->fresh()->status);
        $this->assertSame(0, LoanUnit::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_unit_milik_alat_lain_ditolak(): void
    {
        $lain = Item::factory()->withUnits(1)->create()->units()->first();

        $this->handover(['unit_ids' => [$this->units[0]->id, $lain->id]])->assertSessionHasErrors('unit_ids');
        $this->assertSame(LoanStatus::ReadyPickup, $this->loan->fresh()->status);
    }

    public function test_unit_yang_sedang_dipinjam_ditolak(): void
    {
        $other = Loan::factory()->status(LoanStatus::Borrowed)->between('2026-10-19', '2026-10-22')->create(['item_id' => $this->item->id, 'quantity' => 1]);
        LoanUnit::create(['loan_id' => $other->id, 'item_unit_id' => $this->units[0]->id, 'condition_out' => 'baik']);

        $this->handover()->assertSessionHasErrors('unit_ids');
        $this->assertSame(1, LoanUnit::count(), 'tidak ada loan_units baru');
    }

    public function test_unit_non_available_ditolak(): void
    {
        $this->units[1]->update(['status' => 'maintenance']);

        $this->handover()->assertSessionHasErrors('unit_ids');
    }

    public function test_checklist_harus_lengkap_dan_tidak_meninggalkan_berkas(): void
    {
        $this->handover(['checklist' => [0]])->assertSessionHasErrors('checklist');

        $this->assertSame(LoanStatus::ReadyPickup, $this->loan->fresh()->status);
        $this->assertSame(0, LoanUnit::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_kartu_wajib_diverifikasi_dan_bukti_wajib_diunggah(): void
    {
        $this->handover(['card_verified' => null])->assertSessionHasErrors('card_verified');
        $this->handover(['media' => []])->assertSessionHasErrors('media');
        $this->handover(['media' => [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')]])->assertSessionHasErrors('media.0');
        $this->handover(['media' => [UploadedFile::fake()->create('besar.mp4', 21000, 'video/mp4')]])->assertSessionHasErrors('media.0');

        $this->assertSame(LoanStatus::ReadyPickup, $this->loan->fresh()->status);
    }

    public function test_video_mp4_diterima_dan_bertipe_video(): void
    {
        $this->handover(['media' => [UploadedFile::fake()->create('kondisi.mp4', 500, 'video/mp4')]])->assertSessionHasNoErrors();

        $this->assertSame('video', InspectionMedia::first()->media_type);
    }

    public function test_hanya_untuk_status_ready_pickup(): void
    {
        $this->loan->update(['status' => LoanStatus::Approved]);

        $this->actingAs($this->admin)->get(route('admin.verifikasi.handover.create', $this->loan))
            ->assertRedirect(route('admin.verifikasi.show', $this->loan))
            ->assertSessionHas('toast.title', 'Belum bisa serah terima');

        $this->handover()->assertSessionHas('toast.title', 'Status sudah berubah');
        $this->assertSame(LoanStatus::Approved, $this->loan->fresh()->status);
        $this->assertSame(0, LoanUnit::count());
    }

    public function test_kirim_dua_kali_tidak_menggandakan_serah_terima(): void
    {
        $this->handover()->assertSessionHasNoErrors();
        $this->handover(['media' => [UploadedFile::fake()->create('lagi.jpg', 50, 'image/jpeg')]])
            ->assertSessionHas('toast.title', 'Status sudah berubah');

        $this->assertSame(2, LoanUnit::count());
        $this->assertSame(1, InspectionMedia::count());
    }

    public function test_item_tanpa_template_memakai_checklist_umum(): void
    {
        $item = Item::factory()->withUnits(1)->create(['checklist_template' => null]);
        $loan = Loan::factory()->status(LoanStatus::ReadyPickup)->between('2026-10-21', '2026-10-22')
            ->create(['item_id' => $item->id, 'quantity' => 1]);

        $this->handover(['unit_ids' => [$item->units()->first()->id], 'checklist' => [0, 1, 2]], $loan)->assertSessionHasNoErrors();

        $this->assertSame(LoanStatus::Borrowed, $loan->fresh()->status);
        $this->assertCount(3, $loan->inspections()->first()->checklist);
    }

    public function test_bukti_inspeksi_hanya_bisa_dibuka_pemilik_dan_admin(): void
    {
        $this->handover();
        $media = InspectionMedia::firstOrFail();

        $this->actingAs($this->siswa)->get(route('berkas.media', $media))->assertOk();
        $this->actingAs($this->admin)->get(route('berkas.media', $media))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('berkas.media', $media))->assertForbidden();
    }

    public function test_siswa_tidak_bisa_melakukan_serah_terima(): void
    {
        $this->actingAs($this->siswa)->get(route('admin.verifikasi.handover.create', $this->loan))->assertForbidden();
        $this->actingAs($this->siswa)->post(route('admin.verifikasi.handover.store', $this->loan), $this->payload())->assertForbidden();
        $this->assertSame(LoanStatus::ReadyPickup, $this->loan->fresh()->status);
    }
}
