<?php

namespace Tests\Feature\Siswa;

use App\Enums\LoanStatus;
use App\Models\Item;
use App\Models\Loan;
use App\Models\LoanUnit;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BuktiPeminjamanTest extends TestCase
{
    use RefreshDatabase;

    private User $siswa;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-21 10:00:00'));
        $this->siswa = User::factory()->create(['name' => 'Raka Pratama']);
        $this->item = Item::factory()->withUnits(2)->create(['name' => 'Kamera Bukti']);
    }

    private function requirePdf(): void
    {
        if (! class_exists(Pdf::class)) {
            $this->markTestSkipped('Paket barryvdh/laravel-dompdf belum terpasang: jalankan "composer require barryvdh/laravel-dompdf".');
        }
    }

    private function loan(LoanStatus $status, array $o = []): Loan
    {
        return Loan::factory()->status($status)->between('2026-10-21', '2026-10-23')->create(array_merge([
            'user_id' => $this->siswa->id, 'item_id' => $this->item->id, 'request_code' => 'REQ-2026-1234',
            'pickup_date' => '2026-10-21', 'pickup_time_start' => '09:00', 'pickup_time_end' => '11:00', 'pickup_location' => 'Ruang Sarpras',
        ], $o));
    }

    public function test_pemilik_mengunduh_bukti_pdf_setelah_disetujui(): void
    {
        $this->requirePdf();
        foreach ([LoanStatus::Approved, LoanStatus::ReadyPickup, LoanStatus::Borrowed, LoanStatus::Overdue,
            LoanStatus::Returned, LoanStatus::ReturnedDamaged, LoanStatus::ReturnedLost] as $status) {
            $loan = $this->loan($status, ['request_code' => 'REQ-2026-'.random_int(2000, 8999)]);

            $response = $this->actingAs($this->siswa)->get(route('pengajuan.bukti', $loan));

            $response->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $response->getContent(), $status->value);
            $this->assertStringContainsString("bukti-peminjaman-{$loan->request_code}.pdf", $response->headers->get('content-disposition'));
            $loan->forceDelete();
        }
    }

    public function test_belum_disetujui_atau_berakhir_tanpa_persetujuan_tidak_tersedia(): void
    {
        foreach ([LoanStatus::Pending, LoanStatus::Reviewing, LoanStatus::Rejected, LoanStatus::Cancelled, LoanStatus::Expired] as $status) {
            $loan = $this->loan($status, ['request_code' => 'REQ-2026-'.random_int(2000, 8999)]);

            $this->actingAs($this->siswa)->get(route('pengajuan.bukti', $loan))->assertNotFound();
            $loan->forceDelete();
        }
    }

    public function test_siswa_lain_ditolak_admin_dan_pemilik_boleh_tamu_diarahkan(): void
    {
        $this->requirePdf();
        $loan = $this->loan(LoanStatus::Approved);

        $this->actingAs(User::factory()->create())->get(route('pengajuan.bukti', $loan))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('pengajuan.bukti', $loan))->assertOk()->assertHeader('content-type', 'application/pdf');
        auth()->guard()->logout();
        $this->get(route('pengajuan.bukti', $loan))->assertRedirect(route('login'));
    }

    public function test_tombol_unduh_tampil_hanya_setelah_disetujui(): void
    {
        $approved = $this->loan(LoanStatus::Approved, ['request_code' => 'REQ-2026-3001']);
        $pending = $this->loan(LoanStatus::Pending, ['request_code' => 'REQ-2026-3002']);

        $this->actingAs($this->siswa)->get(route('pengajuan.show', $approved))
            ->assertSee('Unduh bukti peminjaman')->assertSee(route('pengajuan.bukti', $approved), false);
        $this->actingAs($this->siswa)->get(route('pengajuan.show', $pending))->assertDontSee('Unduh bukti peminjaman');
    }

    public function test_bukti_memuat_unit_yang_diserahkan_dan_keterlambatan(): void
    {
        $this->requirePdf();
        $loan = $this->loan(LoanStatus::Returned, [
            'borrowed_at' => '2026-10-21 09:30:00', 'returned_at' => '2026-10-24 10:00:00', 'is_late' => true, 'late_days' => 1,
        ]);
        LoanUnit::create(['loan_id' => $loan->id, 'item_unit_id' => $this->item->units()->first()->id, 'condition_out' => 'baik']);

        $this->actingAs($this->siswa)->get(route('pengajuan.bukti', $loan))->assertOk();
        // Isi dirender dari view yang sama; pastikan view dapat dirender dengan data lengkap.
        $html = view('pdf.bukti', ['loan' => $loan->load(['user', 'item.category', 'loanUnits.unit', 'reviewer']), 'generatedAt' => now()])->render();

        $this->assertStringContainsString('REQ-2026-1234', $html);
        $this->assertStringContainsString('Raka Pratama', $html);
        $this->assertStringContainsString('Kamera Bukti', $html);
        $this->assertStringContainsString($this->item->units()->first()->unit_code, $html);
        $this->assertStringContainsString('terlambat 1 hari', $html);
    }
}
