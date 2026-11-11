<?php

namespace Tests\Feature\Admin;

use App\Enums\LoanStatus;
use App\Jobs\ExpireUnclaimed;
use App\Jobs\MarkOverdue;
use App\Jobs\PromoteReadyPickup;
use App\Jobs\RemindAdminPending;
use App\Jobs\SendDueReminders;
use App\Models\ActivityLog;
use App\Models\Item;
use App\Models\Loan;
use App\Models\LoanUnit;
use App\Models\Setting;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\ReturnService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SchedulerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $siswa;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->admin = User::factory()->admin()->create();
        $this->siswa = User::factory()->create();
        $this->item = Item::factory()->withUnits(3)->create(['name' => 'Kamera Uji']);
    }

    private function at(string $when): void
    {
        $this->travelTo(Carbon::parse($when));
    }

    private function loan(LoanStatus $status, string $start, string $end, array $attrs = []): Loan
    {
        return Loan::factory()->status($status)->between($start, $end)->create(array_merge([
            'user_id' => $this->siswa->id, 'item_id' => $this->item->id, 'quantity' => 1, 'pickup_location' => 'Ruang Sarpras',
        ], $attrs));
    }

    private function kinds($user): array
    {
        return $user->notifications->pluck('data.kind')->all();
    }

    // -------------------------------------------------------------- PromoteReadyPickup

    public function test_promote_ready_pickup_hanya_approved_dengan_jadwal_hari_ini(): void
    {
        $this->at('2026-10-19 06:00:00'); // Senin

        $today = $this->loan(LoanStatus::Approved, '2026-10-19', '2026-10-21', ['pickup_date' => '2026-10-19']);
        $tomorrow = $this->loan(LoanStatus::Approved, '2026-10-20', '2026-10-21', ['pickup_date' => '2026-10-20']);
        $cancelled = $this->loan(LoanStatus::Cancelled, '2026-10-19', '2026-10-21', ['pickup_date' => '2026-10-19']);

        PromoteReadyPickup::dispatchSync();

        $this->assertSame(LoanStatus::ReadyPickup, $today->fresh()->status);
        $this->assertSame(LoanStatus::Approved, $tomorrow->fresh()->status);
        $this->assertSame(LoanStatus::Cancelled, $cancelled->fresh()->status);
        $this->assertTrue(ActivityLog::where('loan_id', $today->id)->where('action', 'ready_pickup')->whereNull('actor_id')->exists());
        $this->assertSame(['ready_pickup'], $this->kinds($this->siswa));

        PromoteReadyPickup::dispatchSync(); // idempoten
        $this->assertSame(['ready_pickup'], $this->kinds($this->siswa->fresh()));
    }

    // -------------------------------------------------------------- SendDueReminders

    public function test_pengingat_h_min_1_dan_hari_h_tanpa_duplikat(): void
    {
        $this->at('2026-10-19 07:00:00'); // Senin

        $besok = $this->loan(LoanStatus::Borrowed, '2026-10-15', '2026-10-20', ['request_code' => 'REQ-2026-6001']);
        $hariIni = $this->loan(LoanStatus::Borrowed, '2026-10-15', '2026-10-19', ['request_code' => 'REQ-2026-6002']);
        $nanti = $this->loan(LoanStatus::Borrowed, '2026-10-15', '2026-10-22', ['request_code' => 'REQ-2026-6003']);
        $returned = $this->loan(LoanStatus::Returned, '2026-10-15', '2026-10-19');

        SendDueReminders::dispatchSync();
        SendDueReminders::dispatchSync(); // tidak boleh dobel pada hari yang sama

        $messages = $this->siswa->fresh()->notifications->pluck('data.message')->implode(' | ');
        $this->assertSame(2, $this->siswa->fresh()->notifications->count());
        $this->assertStringContainsString('besok', $this->siswa->notifications->firstWhere(fn ($n) => str_contains($n->data['message'], 'Kamera Uji') && $n->data['loan_id'] === $besok->id)->data['message']);
        $this->assertStringContainsString('hari ini', $this->siswa->notifications->firstWhere(fn ($n) => $n->data['loan_id'] === $hariIni->id)->data['message']);
        $this->assertNotNull($besok->fresh()->due_reminder_sent_at);
        $this->assertNotNull($hariIni->fresh()->due_reminder_sent_at);
        $this->assertNull($nanti->fresh()->due_reminder_sent_at);
        $this->assertNull($returned->fresh()->due_reminder_sent_at);
        $this->assertSame(2, ActivityLog::where('action', 'due_reminder')->count(), $messages);
    }

    public function test_pengingat_h_min_1_melewati_akhir_pekan(): void
    {
        $senin = $this->loan(LoanStatus::Borrowed, '2026-10-20', '2026-10-26'); // jatuh tempo Senin

        $this->at('2026-10-24 07:00:00'); // Sabtu: bukan hari kerja
        SendDueReminders::dispatchSync();
        $this->assertSame(0, $this->siswa->fresh()->notifications->count());

        $this->at('2026-10-23 07:00:00'); // Jumat = hari kerja sebelum Senin
        SendDueReminders::dispatchSync();
        $this->assertSame(1, $this->siswa->fresh()->notifications->count());
        $this->assertNotNull($senin->fresh()->due_reminder_sent_at);

        $this->at('2026-10-26 07:00:00'); // hari-H: pengingat baru karena hari berbeda
        SendDueReminders::dispatchSync();
        $this->assertSame(2, $this->siswa->fresh()->notifications->count());
    }

    // -------------------------------------------------------------- MarkOverdue

    public function test_mark_overdue(): void
    {
        $this->at('2026-10-19 15:30:00');

        $late = $this->loan(LoanStatus::Borrowed, '2026-10-12', '2026-10-16');
        $dueToday = $this->loan(LoanStatus::Borrowed, '2026-10-15', '2026-10-19');
        $already = $this->loan(LoanStatus::Overdue, '2026-10-10', '2026-10-14');

        MarkOverdue::dispatchSync();

        $this->assertSame(LoanStatus::Overdue, $late->fresh()->status);
        $this->assertSame(LoanStatus::Borrowed, $dueToday->fresh()->status, 'hari-H belum terlambat');
        $this->assertSame(LoanStatus::Overdue, $already->fresh()->status);
        $this->assertSame(['overdue'], $this->kinds($this->siswa));
        $this->assertSame(['overdue'], $this->kinds($this->admin));
        $this->assertTrue(ActivityLog::where('loan_id', $late->id)->where('action', 'overdue')->whereNull('actor_id')->exists());

        MarkOverdue::dispatchSync(); // idempoten
        $this->assertSame(['overdue'], $this->kinds($this->siswa->fresh()));
    }

    // -------------------------------------------------------------- ExpireUnclaimed

    public function test_expire_unclaimed_setelah_toleransi_dan_reservasi_lepas(): void
    {
        $approved = $this->loan(LoanStatus::Approved, '2026-10-19', '2026-10-21', ['pickup_date' => '2026-10-19', 'quantity' => 2]);
        $ready = $this->loan(LoanStatus::ReadyPickup, '2026-10-19', '2026-10-21', ['pickup_date' => '2026-10-19']);
        $borrowed = $this->loan(LoanStatus::Borrowed, '2026-10-15', '2026-10-21');
        $free = fn () => app(AvailabilityService::class)->availableQty($this->item, '2026-10-19', '2026-10-21');

        $this->at('2026-10-20 14:00:00'); // Selasa: toleransi 1 hari kerja belum lewat jam tutup
        ExpireUnclaimed::dispatchSync();
        $this->assertSame(LoanStatus::Approved, $approved->fresh()->status);
        $this->assertSame(0, $free());

        $this->at('2026-10-20 15:30:00'); // lewat batas
        ExpireUnclaimed::dispatchSync();

        $this->assertSame(LoanStatus::Expired, $approved->fresh()->status);
        $this->assertNotNull($approved->fresh()->expired_at);
        $this->assertSame(LoanStatus::Expired, $ready->fresh()->status);
        $this->assertSame(LoanStatus::Borrowed, $borrowed->fresh()->status);
        $this->assertSame(2, $free(), 'reservasi approved/ready_pickup lepas, tinggal borrowed');
        $this->assertSame(['expired', 'expired'], $this->kinds($this->siswa));
        $this->assertSame(['expired', 'expired'], $this->kinds($this->admin));
        $this->assertTrue(ActivityLog::where('loan_id', $approved->id)->where('action', 'expired')->whereNull('actor_id')->exists());
    }

    public function test_toleransi_pengambilan_mengikuti_pengaturan(): void
    {
        Setting::put('pickup_grace_workdays', '0');
        $loan = $this->loan(LoanStatus::Approved, '2026-10-19', '2026-10-21', ['pickup_date' => '2026-10-19']);

        $this->at('2026-10-19 14:00:00');
        ExpireUnclaimed::dispatchSync();
        $this->assertSame(LoanStatus::Approved, $loan->fresh()->status);

        $this->at('2026-10-19 15:30:00');
        ExpireUnclaimed::dispatchSync();
        $this->assertSame(LoanStatus::Expired, $loan->fresh()->status);
    }

    public function test_toleransi_melewati_akhir_pekan(): void
    {
        $loan = $this->loan(LoanStatus::Approved, '2026-10-23', '2026-10-27', ['pickup_date' => '2026-10-23']); // Jumat

        $this->at('2026-10-24 16:00:00'); // Sabtu
        ExpireUnclaimed::dispatchSync();
        $this->assertSame(LoanStatus::Approved, $loan->fresh()->status, 'hari kerja berikutnya adalah Senin');

        $this->at('2026-10-26 15:30:00'); // Senin lewat jam tutup
        ExpireUnclaimed::dispatchSync();
        $this->assertSame(LoanStatus::Expired, $loan->fresh()->status);
    }

    // -------------------------------------------------------------- RemindAdminPending

    public function test_pengingat_admin_untuk_pending_melewati_sla(): void
    {
        $pending = $this->loan(LoanStatus::Pending, '2026-10-21', '2026-10-22', ['created_at' => '2026-10-19 10:00:00', 'request_code' => 'REQ-2026-7001']);
        $reviewing = $this->loan(LoanStatus::Reviewing, '2026-10-21', '2026-10-22', ['created_at' => '2026-10-19 10:00:00']);

        $this->at('2026-10-20 09:00:00'); // belum 1 hari kerja
        RemindAdminPending::dispatchSync();
        $this->assertSame([], $this->kinds($this->admin));

        $this->at('2026-10-20 11:00:00'); // melewati SLA
        RemindAdminPending::dispatchSync();
        $this->assertSame(['sla_pending'], $this->kinds($this->admin->fresh()));
        $this->assertNotNull($pending->fresh()->pending_reminder_sent_at);
        $this->assertNull($reviewing->fresh()->pending_reminder_sent_at, 'hanya status pending');

        $this->at('2026-10-20 13:00:00'); // jam berikutnya, hari yang sama: tidak diulang
        RemindAdminPending::dispatchSync();
        $this->assertSame(['sla_pending'], $this->kinds($this->admin->fresh()));

        $this->at('2026-10-21 08:00:00'); // hari berikutnya: diingatkan lagi
        RemindAdminPending::dispatchSync();
        $this->assertSame(['sla_pending', 'sla_pending'], $this->kinds($this->admin->fresh()));
    }

    public function test_pengingat_admin_diringkas_dan_tidak_berjalan_di_akhir_pekan(): void
    {
        $this->loan(LoanStatus::Pending, '2026-10-21', '2026-10-22', ['created_at' => '2026-10-19 08:00:00']);
        $this->loan(LoanStatus::Pending, '2026-10-21', '2026-10-22', ['created_at' => '2026-10-19 09:00:00']);

        $this->at('2026-10-24 10:00:00'); // Sabtu
        RemindAdminPending::dispatchSync();
        $this->assertSame(0, $this->admin->fresh()->notifications->count());

        $this->at('2026-10-21 10:00:00');
        RemindAdminPending::dispatchSync();
        $this->assertSame(1, $this->admin->fresh()->notifications->count(), 'satu notifikasi ringkas');
        $this->assertStringContainsString('2 pengajuan', $this->admin->notifications->first()->data['message']);
    }

    public function test_sla_mengikuti_pengaturan(): void
    {
        Setting::put('review_sla_workdays', '3');
        $this->loan(LoanStatus::Pending, '2026-10-21', '2026-10-22', ['created_at' => '2026-10-19 10:00:00']);

        $this->at('2026-10-21 12:00:00'); // baru 2 hari kerja
        RemindAdminPending::dispatchSync();
        $this->assertSame(0, $this->admin->fresh()->notifications->count());

        $this->at('2026-10-22 11:00:00'); // 3 hari kerja terlewati
        RemindAdminPending::dispatchSync();
        $this->assertSame(1, $this->admin->fresh()->notifications->count());
    }

    // -------------------------------------------------------------- jadwal & alur akhir

    public function test_jadwal_terdaftar_sesuai_spesifikasi(): void
    {
        Artisan::call('schedule:list');

        $events = collect(app(Schedule::class)->events())
            ->mapWithKeys(fn ($e) => [class_basename((string) $e->description) => $e->expression]);

        $this->assertSame('0 6 * * *', $events['PromoteReadyPickup']);
        $this->assertSame('0 7 * * *', $events['SendDueReminders']);
        $this->assertSame('30 15 * * *', $events['MarkOverdue']);
        $this->assertSame('30 15 * * *', $events['ExpireUnclaimed']);
        $this->assertArrayHasKey('RemindAdminPending', $events->all());
    }

    public function test_siklus_terlambat_dari_borrowed_ke_overdue_hingga_dikembalikan(): void
    {
        $loan = $this->loan(LoanStatus::Borrowed, '2026-10-15', '2026-10-19', ['borrowed_at' => '2026-10-15 09:00:00']);
        LoanUnit::create(['loan_id' => $loan->id, 'item_unit_id' => $this->item->units()->first()->id, 'condition_out' => 'baik']);

        $this->at('2026-10-20 15:30:00');
        MarkOverdue::dispatchSync();
        $this->assertSame(LoanStatus::Overdue, $loan->fresh()->status);

        $this->at('2026-10-21 10:00:00');
        app(ReturnService::class)->complete($loan, $this->admin, [$this->item->units()->first()->id => 'baik'], [0, 1], null, []);

        $loan = $loan->fresh();
        $this->assertSame(LoanStatus::Returned, $loan->status);
        $this->assertTrue($loan->is_late);
        $this->assertSame(2, $loan->late_days);
        $this->assertSame(1, $this->siswa->fresh()->late_count);
        $this->assertSame(
            ['overdue', 'returned'],
            ActivityLog::where('loan_id', $loan->id)->whereIn('action', ['overdue', 'returned'])->orderBy('id')->pluck('action')->all()
        );
    }
}
