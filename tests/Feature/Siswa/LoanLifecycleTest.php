<?php

namespace Tests\Feature\Siswa;

use App\Enums\LoanStatus;
use App\Exceptions\IllegalTransitionException;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LoanLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $siswa;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo(Carbon::parse('2026-10-19 09:00:00'));
        $this->siswa = User::factory()->create();
        $this->item = Item::factory()->withUnits(2)->create();
    }

    private function loan(LoanStatus $status, ?User $owner = null, array $attrs = []): Loan
    {
        return Loan::factory()->status($status)->between('2026-10-21', '2026-10-23')->create(array_merge([
            'user_id' => ($owner ?? $this->siswa)->id,
            'item_id' => $this->item->id,
            'quantity' => 2,
        ], $attrs));
    }

    private function free(): int
    {
        return app(AvailabilityService::class)->availableQty($this->item, '2026-10-21', '2026-10-23');
    }

    public function test_siswa_membatalkan_pending_dan_stok_kembali(): void
    {
        $loan = $this->loan(LoanStatus::Pending);
        $admin = User::factory()->admin()->create();
        $this->assertSame(0, $this->free());

        $this->actingAs($this->siswa)
            ->post(route('pengajuan.cancel', $loan), ['cancel_reason' => 'Tidak jadi'])
            ->assertRedirect(route('pengajuan.show', $loan));

        $loan->refresh();
        $this->assertSame(LoanStatus::Cancelled, $loan->status);
        $this->assertSame('Tidak jadi', $loan->cancel_reason);
        $this->assertNotNull($loan->cancelled_at);
        $this->assertSame(2, $this->free(), 'reservasi dilepas otomatis');
        $this->assertSame(1, $admin->notifications()->count());
        $this->assertDatabaseHas('activity_logs', ['loan_id' => $loan->id, 'action' => 'cancelled']);
    }

    public function test_boleh_membatalkan_pada_status_approved_dan_ready_pickup(): void
    {
        foreach ([LoanStatus::Reviewing, LoanStatus::Approved, LoanStatus::ReadyPickup] as $status) {
            $loan = $this->loan($status);
            $this->actingAs($this->siswa)->post(route('pengajuan.cancel', $loan))->assertRedirect();
            $this->assertSame(LoanStatus::Cancelled, $loan->fresh()->status, $status->value);
        }
    }

    public function test_tidak_bisa_membatalkan_saat_borrowed_atau_status_akhir(): void
    {
        foreach ([LoanStatus::Borrowed, LoanStatus::Overdue, LoanStatus::Returned, LoanStatus::Rejected] as $status) {
            $loan = $this->loan($status);
            $this->actingAs($this->siswa)->post(route('pengajuan.cancel', $loan))->assertForbidden();
            $this->assertSame($status, $loan->fresh()->status);
        }
    }

    public function test_siswa_lain_tidak_bisa_melihat_atau_membatalkan(): void
    {
        $loan = $this->loan(LoanStatus::Pending);
        $lain = User::factory()->create();

        $this->actingAs($lain)->get(route('pengajuan.show', $loan))->assertForbidden();
        $this->actingAs($lain)->post(route('pengajuan.cancel', $loan))->assertForbidden();
        $this->assertSame(LoanStatus::Pending, $loan->fresh()->status);
    }

    public function test_penolakan_melepas_reservasi(): void
    {
        $loan = $this->loan(LoanStatus::Pending);
        $admin = User::factory()->admin()->create();
        $this->assertSame(0, $this->free());

        app(LoanService::class)->transition($loan, LoanStatus::Rejected, $admin, ['reject_reason' => 'Bentrok jadwal']);

        $this->assertSame(2, $this->free());
        $this->assertDatabaseHas('activity_logs', ['loan_id' => $loan->id, 'action' => 'rejected', 'actor_id' => $admin->id]);
    }

    public function test_transisi_ilegal_ditolak(): void
    {
        $svc = app(LoanService::class);

        foreach ([
            [LoanStatus::Pending, LoanStatus::Borrowed],
            [LoanStatus::Borrowed, LoanStatus::Pending],
            [LoanStatus::Returned, LoanStatus::Borrowed],
            [LoanStatus::Rejected, LoanStatus::Approved],
            [LoanStatus::Cancelled, LoanStatus::Pending],
            [LoanStatus::Overdue, LoanStatus::Cancelled],
        ] as [$from, $to]) {
            $loan = $this->loan($from, null, ['quantity' => 1]);
            try {
                $svc->transition($loan, $to);
                $this->fail("{$from->value} → {$to->value} seharusnya ditolak");
            } catch (IllegalTransitionException) {
                $this->assertSame($from, $loan->fresh()->status);
            }
        }
    }

    public function test_alur_normal_dan_tabel_transisi(): void
    {
        $svc = app(LoanService::class);
        $loan = $this->loan(LoanStatus::Pending);

        foreach ([LoanStatus::Reviewing, LoanStatus::Approved, LoanStatus::ReadyPickup, LoanStatus::Borrowed, LoanStatus::Overdue, LoanStatus::Returned] as $to) {
            $loan = $svc->transition($loan, $to);
            $this->assertSame($to, $loan->status);
        }

        foreach ([LoanStatus::Returned, LoanStatus::ReturnedDamaged, LoanStatus::ReturnedLost,
            LoanStatus::Rejected, LoanStatus::Cancelled, LoanStatus::Expired] as $final) {
            $this->assertTrue($final->isFinal(), $final->value);
        }
        $this->assertFalse(LoanStatus::Returned->holdsStock());
        $this->assertTrue(LoanStatus::Overdue->holdsStock());
    }

    public function test_ajukan_ulang_menyalin_data_dan_menautkan_pengajuan_lama(): void
    {
        $old = $this->loan(LoanStatus::Rejected, null, ['quantity' => 1, 'purpose' => 'Dokumentasi pentas seni sekolah', 'student_card_path' => 'student-cards/lama.png', 'request_code' => 'REQ-2026-0001']);
        Storage::disk('local')->put('student-cards/lama.png', 'png');

        $this->actingAs($this->siswa)
            ->get(route('katalog.show', ['item' => $this->item, 'ulang' => $old->id]))
            ->assertOk()
            ->assertSee('Dokumentasi pentas seni sekolah');

        $this->actingAs($this->siswa)->post(route('pengajuan.store', $this->item), [
            'quantity' => 1,
            'start_date' => '2026-10-26',
            'end_date' => '2026-10-27',
            'purpose' => 'Dokumentasi pentas seni sekolah',
            'agreement_accepted' => '1',
            'resubmitted_from_id' => $old->id,
        ])->assertSessionHasNoErrors();

        $new = Loan::where('resubmitted_from_id', $old->id)->firstOrFail();
        $this->assertSame(LoanStatus::Pending, $new->status);
        $this->assertNotSame($old->student_card_path, $new->student_card_path);
        Storage::disk('local')->assertExists($new->student_card_path);

        // Pengajuan lama yang sudah diajukan ulang tidak tampil lagi di "Pengajuan Saya".
        $this->actingAs($this->siswa)->get(route('pengajuan.index'))->assertDontSee($old->request_code);
    }

    public function test_ajukan_ulang_hanya_untuk_pengajuan_ditolak_milik_sendiri(): void
    {
        $pending = $this->loan(LoanStatus::Pending);
        $rejectedLain = $this->loan(LoanStatus::Rejected, User::factory()->create());

        $this->actingAs($this->siswa)->get(route('katalog.show', ['item' => $this->item, 'ulang' => $pending->id]))->assertForbidden();
        $this->actingAs($this->siswa)->get(route('katalog.show', ['item' => $this->item, 'ulang' => $rejectedLain->id]))->assertForbidden();

        $this->actingAs($this->siswa)->post(route('pengajuan.store', $this->item), [
            'quantity' => 1, 'start_date' => '2026-10-26', 'end_date' => '2026-10-27',
            'purpose' => 'Dokumentasi kegiatan OSIS', 'agreement_accepted' => '1',
            'resubmitted_from_id' => $rejectedLain->id,
            'student_card' => UploadedFile::fake()->create('k.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('resubmitted_from_id');
    }

    public function test_halaman_pelacakan_menampilkan_stepper_dan_jadwal(): void
    {
        $loan = $this->loan(LoanStatus::ReadyPickup, null, [
            'pickup_date' => '2026-10-21', 'pickup_time_start' => '09:00', 'pickup_time_end' => '11:00',
            'pickup_location' => 'Ruang Sarpras, Gedung B',
        ]);

        $this->actingAs($this->siswa)->get(route('pengajuan.show', $loan))
            ->assertOk()
            ->assertSee('Menunggu Pengambilan')
            ->assertSee('Jadwal pengambilan')
            ->assertSee('Bawa kartu pelajar asli')
            ->assertSee('Ruang Sarpras, Gedung B');
    }

    public function test_perpanjangan_pinjaman_terlambat_menggeser_jatuh_tempo_ke_masa_depan(): void
    {
        $svc = app(LoanService::class);
        $loan = $this->loan(LoanStatus::Borrowed, null, [
            'start_date' => '2026-10-21', 'end_date' => '2026-10-23', 'borrowed_at' => '2026-10-21 10:00:00',
        ]);

        $this->travelTo('2026-10-24 09:00:00');
        $this->assertSame('2026-10-23', $loan->end_date->toDateString());

        $svc->extend($loan, 2);
        $loan->refresh();
        $this->assertSame('2026-10-25', $loan->end_date->toDateString(), 'end_date baru setelah perpanjangan 2 hari');
        $this->assertSame(2, $loan->extension_days);

        $this->travelTo('2026-10-25 16:00:00');
        $svc->markOverdue($loan);
        $loan->refresh();
        $this->assertSame(LoanStatus::Overdue, $loan->status, 'markOverdue tidak langsung menandai setelah perpanjangan');
    }
}
