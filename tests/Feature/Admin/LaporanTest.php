<?php

namespace Tests\Feature\Admin;

use App\Enums\LoanStatus;
use App\Exports\LoansExport;
use App\Models\Category;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Category $catA;

    private Category $catB;

    private Item $itemA;

    private Item $itemB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-21 10:00:00'));

        $this->admin = User::factory()->admin()->create();
        $this->catA = Category::factory()->create(['name' => 'Kategori A']);
        $this->catB = Category::factory()->create(['name' => 'Kategori B']);
        $this->itemA = Item::factory()->create(['category_id' => $this->catA->id, 'name' => 'Alat A']);
        $this->itemB = Item::factory()->create(['category_id' => $this->catB->id, 'name' => 'Alat B']);
    }

    private function requireExcel(): void
    {
        if (! class_exists(Excel::class)) {
            $this->markTestSkipped('Paket maatwebsite/excel belum terpasang: jalankan "composer require maatwebsite/excel" (butuh ekstensi PHP gd dan zip).');
        }
    }

    private function requirePdf(): void
    {
        if (! class_exists(Pdf::class)) {
            $this->markTestSkipped('Paket barryvdh/laravel-dompdf belum terpasang: jalankan "composer require barryvdh/laravel-dompdf".');
        }
    }

    private function loan(string $code, Item $item, LoanStatus $status, string $start, string $end, array $o = []): Loan
    {
        return Loan::factory()->status($status)->between($start, $end)->create(array_merge(['request_code' => $code, 'item_id' => $item->id], $o));
    }

    /** Data standar: total 5, disetujui 2, ditolak/batal 2, rata-rata durasi (3+1+5+3+4)/5 = 3,2 hari. */
    private function seedStandard(): void
    {
        $this->loan('REQ-2026-9001', $this->itemA, LoanStatus::Returned, '2026-10-19', '2026-10-21');
        $this->loan('REQ-2026-9002', $this->itemA, LoanStatus::Rejected, '2026-10-20', '2026-10-20');
        $this->loan('REQ-2026-9003', $this->itemB, LoanStatus::Cancelled, '2026-10-22', '2026-10-26');
        $this->loan('REQ-2026-9004', $this->itemB, LoanStatus::Borrowed, '2026-10-21', '2026-10-23');
        $this->loan('REQ-2026-9005', $this->itemB, LoanStatus::Expired, '2026-10-20', '2026-10-23');
    }

    private function summaryOf(array $query = []): array
    {
        return $this->actingAs($this->admin)->get(route('admin.laporan.index', $query))->assertOk()->viewData('summary');
    }

    public function test_kartu_ringkasan(): void
    {
        $this->seedStandard();

        $this->assertSame(['total' => 5, 'approved' => 2, 'declined' => 2, 'avg_days' => 3.2], $this->summaryOf());

        $this->actingAs($this->admin)->get(route('admin.laporan.index'))
            ->assertSee('Total transaksi')->assertSee('Disetujui')->assertSee('Ditolak / batal')->assertSee('Rata-rata durasi')->assertSee('3,2 hari');
    }

    public function test_semua_status_disetujui_dihitung(): void
    {
        foreach (ReportService::APPROVED as $i => $status) {
            $this->loan('REQ-2026-91'.$i, $this->itemA, $status, '2026-10-20', '2026-10-21');
        }
        $this->loan('REQ-2026-9199', $this->itemA, LoanStatus::Pending, '2026-10-20', '2026-10-21');

        $s = $this->summaryOf();

        $this->assertSame(count(ReportService::APPROVED) + 1, $s['total']);
        $this->assertSame(count(ReportService::APPROVED), $s['approved']);
        $this->assertSame(0, $s['declined']);
    }

    public function test_filter_rentang_tanggal_pada_tanggal_pinjam(): void
    {
        $this->seedStandard();

        // Tanggal mulai: 9001=19, 9002=20, 9005=20, 9004=21, 9003=22
        $this->assertSame(2, $this->summaryOf(['dari' => '2026-10-21', 'sampai' => '2026-10-22'])['total']);
        $this->assertSame(4, $this->summaryOf(['dari' => '2026-10-20'])['total'], 'hanya batas bawah');
        $this->assertSame(1, $this->summaryOf(['sampai' => '2026-10-19'])['total'], 'hanya batas atas (inklusif)');
        $this->assertSame(0, $this->summaryOf(['dari' => '2026-11-01', 'sampai' => '2026-11-30'])['total']);
    }

    public function test_filter_kategori_dan_status(): void
    {
        $this->seedStandard();

        $this->assertSame(2, $this->summaryOf(['kategori' => $this->catA->id])['total']);
        $this->assertSame(3, $this->summaryOf(['kategori' => $this->catB->id])['total']);
        $this->assertSame(1, $this->summaryOf(['status' => 'returned'])['total']);
        $this->assertSame(1, $this->summaryOf(['kategori' => $this->catB->id, 'status' => 'borrowed'])['total']);
        $this->assertSame(0, $this->summaryOf(['kategori' => $this->catA->id, 'status' => 'borrowed'])['total']);
    }

    public function test_rentang_cepat(): void
    {
        $this->loan('REQ-2026-9101', $this->itemA, LoanStatus::Returned, '2026-10-21', '2026-10-21');   // hari ini
        $this->loan('REQ-2026-9102', $this->itemA, LoanStatus::Returned, '2026-10-16', '2026-10-16');   // 5 hari lalu
        $this->loan('REQ-2026-9103', $this->itemA, LoanStatus::Returned, '2026-10-01', '2026-10-01');   // 20 hari lalu
        $this->loan('REQ-2026-9104', $this->itemA, LoanStatus::Returned, '2026-08-15', '2026-08-15');   // 67 hari lalu
        $this->loan('REQ-2026-9105', $this->itemA, LoanStatus::Returned, '2026-06-30', '2026-06-30');   // semester genap

        $this->assertSame(1, $this->summaryOf(['rentang' => 'hari-ini'])['total']);
        $this->assertSame(2, $this->summaryOf(['rentang' => '7-hari'])['total']);
        $this->assertSame(3, $this->summaryOf(['rentang' => '30-hari'])['total']);
        $this->assertSame(4, $this->summaryOf(['rentang' => 'semester'])['total'], 'semester ganjil = Juli–Desember');
    }

    public function test_rentang_semester_mengikuti_bulan_berjalan(): void
    {
        $svc = app(ReportService::class);

        $this->assertSame(['2026-07-01', '2026-12-31'], $svc->range('semester'));

        $this->travelTo(Carbon::parse('2027-03-10 10:00:00'));
        $this->assertSame(['2027-01-01', '2027-06-30'], $svc->range('semester'));
    }

    public function test_filter_tidak_valid_diabaikan(): void
    {
        $this->seedStandard();

        $s = $this->summaryOf(['dari' => 'bukan-tanggal', 'sampai' => '31/12/2026', 'status' => 'ngawur', 'kategori' => 9999, 'rentang' => 'minggu-depan']);

        $this->assertSame(5, $s['total']);
    }

    public function test_tabel_paginasi(): void
    {
        foreach (range(1, 20) as $i) {
            $this->loan('REQ-2026-92'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), $this->itemA, LoanStatus::Returned, '2026-10-20', '2026-10-21');
        }

        $loans = $this->actingAs($this->admin)->get(route('admin.laporan.index'))->assertOk()->viewData('loans');
        $this->assertSame(20, $loans->total());
        $this->assertSame(15, $loans->count());

        $this->actingAs($this->admin)->get(route('admin.laporan.index', ['page' => 2]))->assertOk();
    }

    public function test_tabel_menampilkan_baris_dan_urut_terbaru(): void
    {
        $this->seedStandard();

        $this->actingAs($this->admin)->get(route('admin.laporan.index'))
            ->assertSee('REQ-2026-9001')->assertSee('Alat A')->assertSee('Kategori A', false)->assertSeeInOrder(['REQ-2026-9003', 'REQ-2026-9001']);
    }

    public function test_keadaan_kosong(): void
    {
        $this->actingAs($this->admin)->get(route('admin.laporan.index'))->assertOk()->assertSee('Tidak ada transaksi');
        $this->assertSame(['total' => 0, 'approved' => 0, 'declined' => 0, 'avg_days' => 0.0], $this->summaryOf());
    }

    // ------------------------------------------------------------------ ekspor

    public function test_ekspor_pdf(): void
    {
        $this->requirePdf();
        $this->seedStandard();

        $response = $this->actingAs($this->admin)->get(route('admin.laporan.pdf'));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('laporan-peminjaman-2026-10-21.pdf', $response->headers->get('content-disposition'));
    }

    public function test_ekspor_pdf_dengan_filter_dan_data_kosong_tidak_error(): void
    {
        $this->requirePdf();
        $this->actingAs($this->admin)->get(route('admin.laporan.pdf', ['status' => 'returned', 'kategori' => $this->catA->id]))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_ekspor_excel_mengikuti_filter_aktif(): void
    {
        $this->requireExcel();
        $this->seedStandard();
        Excel::fake();

        $this->actingAs($this->admin)->get(route('admin.laporan.excel', ['kategori' => $this->catB->id, 'status' => 'borrowed']))->assertOk();

        Excel::assertDownloaded('laporan-peminjaman-2026-10-21.xlsx', function (LoansExport $export) {
            return $export->query()->pluck('request_code')->all() === ['REQ-2026-9004'];
        });
    }

    public function test_ekspor_excel_tanpa_filter_memuat_semua_dan_header_sesuai(): void
    {
        $this->requireExcel();
        $this->seedStandard();
        Excel::fake();

        $this->actingAs($this->admin)->get(route('admin.laporan.excel'))->assertOk();

        Excel::assertDownloaded('laporan-peminjaman-2026-10-21.xlsx', function (LoansExport $export) {
            $codes = $export->query()->pluck('request_code')->sort()->values()->all();
            $row = $export->map($export->query()->where('request_code', 'REQ-2026-9001')->first());

            return $codes === ['REQ-2026-9001', 'REQ-2026-9002', 'REQ-2026-9003', 'REQ-2026-9004', 'REQ-2026-9005']
                && count($export->headings()) === 14
                && $row[0] === 'REQ-2026-9001' && $row[5] === 'Alat A' && $row[8] === '2026-10-19' && $row[10] === 3 && $row[11] === 'Dikembalikan';
        });
    }

    public function test_ekspor_excel_rentang_cepat(): void
    {
        $this->requireExcel();
        $this->loan('REQ-2026-9301', $this->itemA, LoanStatus::Returned, '2026-10-21', '2026-10-21');
        $this->loan('REQ-2026-9302', $this->itemA, LoanStatus::Returned, '2026-08-01', '2026-08-01');
        Excel::fake();

        $this->actingAs($this->admin)->get(route('admin.laporan.excel', ['rentang' => '7-hari']))->assertOk();

        Excel::assertDownloaded('laporan-peminjaman-2026-10-21.xlsx', fn (LoansExport $e) => $e->query()->pluck('request_code')->all() === ['REQ-2026-9301']);
    }

    public function test_laporan_hanya_untuk_admin(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.laporan.index'))->assertForbidden();
        $this->get(route('admin.laporan.pdf'))->assertForbidden();
        $this->get(route('admin.laporan.excel'))->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('admin.laporan.index'))->assertRedirect(route('login'));
    }
}
