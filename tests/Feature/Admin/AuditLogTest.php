<?php

namespace Tests\Feature\Admin;

use App\Enums\LoanStatus;
use App\Models\ActivityLog;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use App\Services\AuditService;
use App\Services\HandoverService;
use App\Services\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo(Carbon::parse('2026-10-19 09:00:00'));
    }

    public function test_alur_lengkap_menulis_audit_log_berurutan(): void
    {
        $siswa = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $item = Item::factory()->withUnits(2)->create(['checklist_template' => ['Lengkap']]);
        $loans = app(LoanService::class);

        $loan = $loans->submit($siswa, $item, [
            'quantity' => 1, 'start_date' => '2026-10-19', 'end_date' => '2026-10-21', 'purpose' => 'Dokumentasi kegiatan sekolah',
        ], UploadedFile::fake()->create('kartu.pdf', 10, 'application/pdf'));

        $loans->startReview($loan, $admin);
        $loans->approve($loan, $admin, [
            'pickup_date' => '2026-10-19', 'pickup_time_start' => '09:00', 'pickup_time_end' => '11:00', 'pickup_location' => 'Ruang Sarpras',
        ]);
        $loans->markReadyPickup($loan, $admin);
        app(HandoverService::class)->handover(
            $loan, $admin, [$item->units()->first()->id], [0], null,
            [UploadedFile::fake()->create('foto.jpg', 10, 'image/jpeg')]
        );

        $this->assertSame(
            ['submitted', 'reviewing', 'approved', 'ready_pickup', 'handover'],
            ActivityLog::where('loan_id', $loan->id)->orderBy('id')->pluck('action')->all()
        );
        $this->assertSame(LoanStatus::Borrowed, $loan->fresh()->status);

        $statusTrail = ActivityLog::where('loan_id', $loan->id)->orderBy('id')->get()
            ->map(fn ($l) => $l->meta['status_to'] ?? null)->filter()->values()->all();
        $this->assertSame(['pending', 'reviewing', 'approved', 'ready_pickup', 'borrowed'], $statusTrail);
    }

    public function test_halaman_audit_menampilkan_dan_menyaring(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Maya Admin']);
        $siswa = User::factory()->create(['name' => 'Raka Siswa']);
        $a = Loan::factory()->create(['request_code' => 'REQ-2026-4001']);
        $b = Loan::factory()->create(['request_code' => 'REQ-2026-4002']);
        $audit = app(AuditService::class);
        $audit->log($a, $admin, 'approved', 'Pengajuan disetujui');
        $audit->log($b, $siswa, 'cancelled', 'Pengajuan dibatalkan oleh siswa');
        $this->travelTo(Carbon::parse('2026-10-25 09:00:00'));
        $audit->log($b, null, 'expired', 'Kedaluwarsa otomatis');

        $this->actingAs($admin);

        $this->get(route('admin.audit.index'))->assertOk()
            ->assertSee('REQ-2026-4001')->assertSee('Kedaluwarsa otomatis')->assertSee('Sistem');
        $this->get(route('admin.audit.index', ['loan' => $a->id]))
            ->assertSee('Pengajuan disetujui')->assertDontSee('Pengajuan dibatalkan oleh siswa');
        $this->get(route('admin.audit.index', ['q' => 'REQ-2026-4002']))
            ->assertSee('cancelled')->assertDontSee('REQ-2026-4001');
        $this->get(route('admin.audit.index', ['aktor' => 'Raka']))
            ->assertSee('Pengajuan dibatalkan oleh siswa')->assertDontSee('Pengajuan disetujui');
        $this->get(route('admin.audit.index', ['aksi' => 'expired']))
            ->assertSee('Kedaluwarsa otomatis')->assertDontSee('Pengajuan disetujui');
        $this->get(route('admin.audit.index', ['dari' => '2026-10-24']))
            ->assertSee('Kedaluwarsa otomatis')->assertDontSee('Pengajuan disetujui');
    }

    public function test_audit_log_read_only_dan_khusus_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/audit')->assertStatus(405);
        $this->actingAs($admin)->delete('/admin/audit')->assertStatus(405);
        $this->actingAs(User::factory()->create())->get(route('admin.audit.index'))->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('admin.audit.index'))->assertRedirect(route('login'));
    }

    public function test_keadaan_kosong(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.audit.index'))
            ->assertOk()->assertSee('Tidak ada catatan');
    }
}
