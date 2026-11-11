<?php

namespace Tests\Feature\Admin;

use App\Enums\LoanStatus;
use App\Models\DamageReport;
use App\Models\InspectionMedia;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Loan;
use App\Models\LoanInspection;
use App\Models\LoanUnit;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReturnTest extends TestCase
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
        $this->travelTo(Carbon::parse('2026-10-21 10:00:00')); // Rabu

        $this->admin = User::factory()->admin()->create(['name' => 'Maya Sari']);
        $this->siswa = User::factory()->create();
        $this->item = Item::factory()->withUnits(4)->create([
            'name' => 'Kamera Uji', 'checklist_template' => ['Body lengkap', 'Charger'],
        ]);
        $this->units = $this->item->units()->orderBy('id')->get();

        $this->loan = Loan::factory()->status(LoanStatus::Borrowed)->between('2026-10-19', '2026-10-23')->create([
            'user_id' => $this->siswa->id, 'item_id' => $this->item->id, 'quantity' => 2, 'borrowed_at' => '2026-10-19 09:00:00',
        ]);
        foreach ([0, 1] as $i) {
            LoanUnit::create(['loan_id' => $this->loan->id, 'item_unit_id' => $this->units[$i]->id, 'condition_out' => 'baik']);
        }
    }

    private function conditions(string $a = 'baik', string $b = 'baik'): array
    {
        return [$this->units[0]->id => $a, $this->units[1]->id => $b];
    }

    private function giveBack(array $o = [], ?Loan $loan = null)
    {
        return $this->actingAs($this->admin)->post(route('admin.pengembalian.store', $loan ?? $this->loan), array_merge([
            'conditions' => $this->conditions(),
            'checklist' => [0, 1],
        ], $o));
    }

    private function photo(string $name = 'bukti.jpg'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 100, 'image/jpeg');
    }

    // ------------------------------------------------------------ kondisi akhir

    public function test_pengembalian_baik(): void
    {
        $this->giveBack()->assertRedirect(route('admin.pengembalian.index'))->assertSessionHasNoErrors();

        $loan = $this->loan->fresh();
        $this->assertSame(LoanStatus::Returned, $loan->status);
        $this->assertNotNull($loan->returned_at);
        $this->assertFalse($loan->is_late);
        $this->assertSame(0, $loan->late_days);
        $this->assertSame(0, $this->siswa->fresh()->late_count);

        $this->assertSame(2, LoanUnit::where('loan_id', $loan->id)->where('condition_in', 'baik')->whereNotNull('returned_at')->count());
        $this->assertSame(4, ItemUnit::where('item_id', $this->item->id)->where('status', 'available')->count());
        $this->assertSame(4, ItemUnit::where('item_id', $this->item->id)->lendable()->count(), 'unit kembali bisa dipinjam');
        $this->assertSame(0, DamageReport::count());

        $inspection = LoanInspection::where('loan_id', $loan->id)->where('type', 'return')->firstOrFail();
        $this->assertSame('baik', $inspection->final_condition->value);
        $this->assertSame($this->admin->id, $inspection->inspected_by);
        $this->assertTrue(collect($inspection->checklist)->every(fn ($c) => $c['checked']));

        $this->assertDatabaseHas('activity_logs', ['loan_id' => $loan->id, 'action' => 'returned', 'actor_id' => $this->admin->id]);
        $notif = $this->siswa->notifications()->first();
        $this->assertSame('returned', $notif->data['kind']);
        $this->assertStringContainsString('Tepat waktu', $notif->data['message']);
    }

    public function test_rusak_hanya_mengubah_unit_terkait(): void
    {
        $this->giveBack([
            'conditions' => $this->conditions('rusak', 'baik'),
            'notes' => 'Lensa retak',
            'media' => [$this->photo()],
        ])->assertSessionHasNoErrors();

        $loan = $this->loan->fresh();
        $this->assertSame(LoanStatus::ReturnedDamaged, $loan->status);

        $this->assertSame('maintenance', $this->units[0]->fresh()->status->value);
        foreach ([1, 2, 3] as $i) {
            $this->assertSame('available', $this->units[$i]->fresh()->status->value, "unit {$i} tidak boleh berubah");
        }

        $report = DamageReport::firstOrFail();
        $this->assertSame($this->units[0]->id, $report->item_unit_id);
        $this->assertSame('rusak', $report->type->value);
        $this->assertSame('open', $report->status->value);
        $this->assertSame('Lensa retak', $report->description);
        $this->assertSame($this->admin->id, $report->reported_by);
        $this->assertSame(1, DamageReport::count());

        // Hanya unit tersebut yang hilang dari stok; jenis alat tetap bisa dipinjam.
        $this->assertSame(3, app(AvailabilityService::class)->availableQty($this->item, '2026-10-26', '2026-10-27'));
        $this->assertSame('returned_damaged', $this->siswa->notifications()->first()->data['kind']);
        $this->assertSame(1, InspectionMedia::count());
    }

    public function test_hilang_hanya_mengubah_unit_terkait_dan_bukti_opsional(): void
    {
        $this->giveBack(['conditions' => $this->conditions('baik', 'hilang'), 'notes' => 'Hilang saat kegiatan'])
            ->assertSessionHasNoErrors();

        $this->assertSame(LoanStatus::ReturnedLost, $this->loan->fresh()->status);
        $this->assertSame('available', $this->units[0]->fresh()->status->value);
        $this->assertSame('lost', $this->units[1]->fresh()->status->value);

        $report = DamageReport::firstOrFail();
        $this->assertSame($this->units[1]->id, $report->item_unit_id);
        $this->assertSame('hilang', $report->type->value);
        $this->assertSame('returned_lost', $this->siswa->notifications()->first()->data['kind']);
    }

    public function test_campuran_rusak_dan_hilang_mengikuti_kondisi_terburuk(): void
    {
        $this->giveBack(['conditions' => $this->conditions('rusak', 'hilang'), 'notes' => 'Satu rusak satu hilang', 'media' => [$this->photo()]])
            ->assertSessionHasNoErrors();

        $this->assertSame(LoanStatus::ReturnedLost, $this->loan->fresh()->status);
        $this->assertSame('maintenance', $this->units[0]->fresh()->status->value);
        $this->assertSame('lost', $this->units[1]->fresh()->status->value);
        $this->assertSame(2, DamageReport::count());
        $this->assertSame('hilang', LoanInspection::where('type', 'return')->first()->final_condition->value);
    }

    // ------------------------------------------------------------ keterlambatan

    public function test_terlambat_mencatat_is_late_late_days_dan_late_count(): void
    {
        $this->loan->update(['status' => LoanStatus::Overdue]);
        $this->travelTo(Carbon::parse('2026-10-26 10:00:00')); // Senin; jatuh tempo Jumat 23

        $this->giveBack()->assertSessionHasNoErrors();

        $loan = $this->loan->fresh();
        $this->assertSame(LoanStatus::Returned, $loan->status);
        $this->assertTrue($loan->is_late);
        $this->assertSame(3, $loan->late_days);
        $this->assertSame(1, $this->siswa->fresh()->late_count);
        $this->assertStringContainsString('Terlambat 3 hari', $this->siswa->notifications()->first()->data['message']);
    }

    public function test_kembali_di_hari_jatuh_tempo_meski_lewat_jam_layanan_tidak_terlambat(): void
    {
        $this->travelTo(Carbon::parse('2026-10-23 17:30:00'));

        $this->giveBack()->assertSessionHasNoErrors();

        $this->assertFalse($this->loan->fresh()->is_late);
        $this->assertSame(0, $this->siswa->fresh()->late_count);
    }

    public function test_keterlambatan_bertambah_pada_late_count_yang_sudah_ada(): void
    {
        $this->siswa->update(['late_count' => 2]);
        $this->travelTo(Carbon::parse('2026-10-24 10:00:00'));

        $this->giveBack();

        $this->assertSame(3, $this->siswa->fresh()->late_count);
        $this->assertSame(1, $this->loan->fresh()->late_days);
    }

    // ------------------------------------------------------------ validasi

    public function test_catatan_dan_bukti_wajib_untuk_unit_rusak(): void
    {
        $this->giveBack(['conditions' => $this->conditions('rusak')])->assertSessionHasErrors(['notes', 'media']);
        $this->giveBack(['conditions' => $this->conditions('rusak'), 'notes' => 'Retak'])->assertSessionHasErrors('media');
        $this->giveBack(['conditions' => $this->conditions('hilang')])->assertSessionHasErrors('notes');

        $this->assertSame(LoanStatus::Borrowed, $this->loan->fresh()->status);
        $this->assertSame(0, DamageReport::count());
    }

    public function test_butir_checklist_tidak_sesuai_mewajibkan_catatan(): void
    {
        $this->giveBack(['checklist' => [0]])->assertSessionHasErrors('notes');
        $this->giveBack(['checklist' => [0], 'notes' => 'Charger tidak dibawa'])->assertSessionHasNoErrors();

        $this->assertSame(LoanStatus::Returned, $this->loan->fresh()->status);
        $this->assertFalse(collect(LoanInspection::first()->checklist)->every(fn ($c) => $c['checked']));
    }

    public function test_kondisi_harus_valid_dan_mencakup_semua_unit_yang_dipinjam(): void
    {
        $this->giveBack(['conditions' => $this->conditions('baik', 'ajaib')])->assertSessionHasErrors('conditions.*');
        $this->giveBack(['conditions' => [$this->units[0]->id => 'baik']])->assertSessionHasErrors('conditions');
        $this->giveBack(['conditions' => [$this->units[0]->id => 'baik', $this->units[2]->id => 'baik']])->assertSessionHasErrors('conditions');
        $this->giveBack(['conditions' => []])->assertSessionHasErrors('conditions');

        $this->assertSame(LoanStatus::Borrowed, $this->loan->fresh()->status);
        $this->assertSame(0, LoanInspection::count());
    }

    public function test_format_bukti_dan_tidak_ada_berkas_yatim_saat_gagal(): void
    {
        $this->giveBack(['media' => [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')]])->assertSessionHasErrors('media.0');
        $this->giveBack(['media' => [UploadedFile::fake()->create('besar.mp4', 21000, 'video/mp4')]])->assertSessionHasErrors('media.0');

        // Gagal di dalam service (unit tidak lengkap) setelah validasi lolos: tidak boleh ada berkas tersisa.
        $this->giveBack(['conditions' => [$this->units[0]->id => 'baik'], 'media' => [$this->photo()]])->assertSessionHasErrors('conditions');
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_video_mp4_diterima(): void
    {
        $this->giveBack(['media' => [UploadedFile::fake()->create('kondisi.mp4', 500, 'video/mp4')]])->assertSessionHasNoErrors();

        $this->assertSame('video', InspectionMedia::first()->media_type);
    }

    // ------------------------------------------------------------ status & akses

    public function test_tidak_bisa_diselesaikan_dua_kali(): void
    {
        $this->giveBack(['conditions' => $this->conditions('rusak'), 'notes' => 'Retak', 'media' => [$this->photo()]]);
        $this->giveBack(['conditions' => $this->conditions('rusak'), 'notes' => 'Retak', 'media' => [$this->photo('lagi.jpg')]])
            ->assertSessionHas('toast.title', 'Status sudah berubah');

        $this->assertSame(1, DamageReport::count());
        $this->assertSame(1, LoanInspection::where('type', 'return')->count());
        $this->assertSame(1, InspectionMedia::count());
    }

    public function test_hanya_borrowed_atau_overdue_yang_bisa_dikembalikan(): void
    {
        foreach ([LoanStatus::Pending, LoanStatus::ReadyPickup, LoanStatus::Returned, LoanStatus::Cancelled] as $status) {
            $loan = Loan::factory()->status($status)->create(['item_id' => $this->item->id]);

            $this->actingAs($this->admin)->get(route('admin.pengembalian.show', $loan))
                ->assertRedirect(route('admin.pengembalian.index'));
            $this->giveBack([], $loan)->assertSessionHas('toast.title', 'Status sudah berubah');
            $this->assertSame($status, $loan->fresh()->status);
        }
    }

    public function test_siswa_tidak_bisa_mengakses_pengembalian(): void
    {
        $this->actingAs($this->siswa)->get(route('admin.pengembalian.index'))->assertForbidden();
        $this->actingAs($this->siswa)->get(route('admin.pengembalian.show', $this->loan))->assertForbidden();
        $this->actingAs($this->siswa)->post(route('admin.pengembalian.store', $this->loan), ['conditions' => $this->conditions()])->assertForbidden();
        $this->assertSame(LoanStatus::Borrowed, $this->loan->fresh()->status);
    }

    // ------------------------------------------------------------ tampilan

    public function test_daftar_pengembalian_badge_filter_dan_pencarian(): void
    {
        $today = Loan::factory()->status(LoanStatus::Borrowed)->between('2026-10-19', '2026-10-21')->create(['item_id' => $this->item->id, 'request_code' => 'REQ-2026-5001']);
        $late = Loan::factory()->status(LoanStatus::Overdue)->between('2026-10-15', '2026-10-20')->create(['item_id' => $this->item->id, 'request_code' => 'REQ-2026-5002']);
        $done = Loan::factory()->status(LoanStatus::Returned)->create(['item_id' => $this->item->id, 'request_code' => 'REQ-2026-5003']);
        LoanUnit::create(['loan_id' => $today->id, 'item_unit_id' => $this->units[3]->id, 'condition_out' => 'baik']);

        $this->actingAs($this->admin);

        $this->get(route('admin.pengembalian.index'))->assertOk()
            ->assertSee('REQ-2026-5001')->assertSee('REQ-2026-5002')->assertDontSee('REQ-2026-5003')
            ->assertSee('Jatuh tempo hari ini')->assertSee('Terlambat 1 hari')->assertSee('Kembali tepat waktu');
        $this->get(route('admin.pengembalian.index', ['f' => 'hari-ini']))
            ->assertSee('REQ-2026-5001')->assertDontSee('REQ-2026-5002');
        $this->get(route('admin.pengembalian.index', ['f' => 'terlambat']))
            ->assertSee('REQ-2026-5002')->assertDontSee('REQ-2026-5001');
        $this->get(route('admin.pengembalian.index', ['q' => $this->units[3]->unit_code]))
            ->assertSee('REQ-2026-5001')->assertDontSee('REQ-2026-5002');
    }

    public function test_layar_pengembalian_menampilkan_pembanding_dan_panel_stok(): void
    {
        $handover = LoanInspection::create([
            'loan_id' => $this->loan->id, 'type' => 'handover', 'checklist' => [['label' => 'Body lengkap', 'checked' => true]],
            'final_condition' => 'baik', 'inspected_by' => $this->admin->id, 'inspected_at' => now(),
        ]);
        $media = InspectionMedia::create(['inspection_id' => $handover->id, 'media_type' => 'photo', 'path' => 'inspections/x/handover/a.jpg']);

        $this->actingAs($this->admin)->get(route('admin.pengembalian.show', $this->loan))
            ->assertOk()
            ->assertSee('Perbandingan kondisi')
            ->assertSee('Sebelum (serah terima)')
            ->assertSee(route('berkas.media', $media), false)
            ->assertSee($this->units[0]->unit_code)
            ->assertSee('Body lengkap')
            ->assertSee('Pembaruan stok otomatis')
            ->assertSee('Ringkasan konfirmasi')
            ->assertSee('Kembali tepat waktu')
            ->assertSee('Selesaikan Pengembalian')
            ->assertSee('Maya Sari');
    }

    public function test_layar_pengembalian_tanpa_foto_serah_terima_tidak_error(): void
    {
        $this->actingAs($this->admin)->get(route('admin.pengembalian.show', $this->loan))
            ->assertOk()->assertSee('Belum ada foto serah terima.');
    }

    public function test_siswa_melihat_bukti_pengembalian(): void
    {
        $this->giveBack();

        $this->actingAs($this->siswa)->get(route('pengajuan.show', $this->loan))
            ->assertOk()
            ->assertSee('Bukti pengembalian')
            ->assertSee('Transaksi selesai')
            ->assertSee('Maya Sari')
            ->assertSee('Tepat waktu');
    }

    public function test_laporan_kerusakan_terbuka_tampil_di_dashboard(): void
    {
        $this->giveBack(['conditions' => $this->conditions('rusak'), 'notes' => 'Retak', 'media' => [$this->photo()]]);

        $this->assertSame(1, app(DashboardService::class)->summary()['attention']['openDamage']);
    }

    public function test_bukti_pengembalian_hanya_bisa_dibuka_pemilik_dan_admin(): void
    {
        $this->giveBack(['conditions' => $this->conditions('rusak'), 'notes' => 'Retak', 'media' => [$this->photo()]]);
        $media = InspectionMedia::firstOrFail();

        $this->actingAs($this->siswa)->get(route('berkas.media', $media))->assertOk();
        $this->actingAs($this->admin)->get(route('berkas.media', $media))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('berkas.media', $media))->assertForbidden();
    }
}
