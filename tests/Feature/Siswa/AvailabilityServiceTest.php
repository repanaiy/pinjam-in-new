<?php

namespace Tests\Feature\Siswa;

use App\Enums\LoanStatus;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Loan;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private Item $item;

    private AvailabilityService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-19 09:00:00')); // Senin
        $this->item = Item::factory()->withUnits(3)->create();
        $this->svc = app(AvailabilityService::class);
    }

    private function loan(int $qty, string $start, string $end, LoanStatus $status = LoanStatus::Pending): Loan
    {
        return Loan::factory()->status($status)->between($start, $end)
            ->create(['item_id' => $this->item->id, 'quantity' => $qty]);
    }

    public function test_tanpa_pinjaman_semua_unit_tersedia(): void
    {
        $this->assertSame(3, $this->svc->availableQty($this->item, '2026-10-21', '2026-10-23'));
    }

    public function test_pinjaman_beririsan_mengurangi_stok_dan_yang_tidak_beririsan_tidak(): void
    {
        $this->loan(2, '2026-10-21', '2026-10-22');

        $this->assertSame(1, $this->svc->availableQty($this->item, '2026-10-22', '2026-10-23'), 'irisan di tanggal 22');
        $this->assertSame(3, $this->svc->availableQty($this->item, '2026-10-23', '2026-10-23'), 'tidak beririsan');
        $this->assertSame(1, $this->svc->availableQty($this->item, '2026-10-21', '2026-10-21'), 'tanggal mulai sama');
    }

    public function test_quantity_beberapa_pinjaman_dijumlahkan(): void
    {
        $this->loan(1, '2026-10-21', '2026-10-23');
        $this->loan(1, '2026-10-22', '2026-10-23');

        $this->assertSame(1, $this->svc->availableQty($this->item, '2026-10-22', '2026-10-22'));
    }

    public function test_status_akhir_tidak_menahan_stok(): void
    {
        foreach ([LoanStatus::Rejected, LoanStatus::Cancelled, LoanStatus::Expired,
            LoanStatus::Returned, LoanStatus::ReturnedDamaged, LoanStatus::ReturnedLost] as $status) {
            $this->loan(3, '2026-10-21', '2026-10-23', $status);
        }

        $this->assertSame(3, $this->svc->availableQty($this->item, '2026-10-21', '2026-10-23'));
    }

    public function test_status_aktif_menahan_stok(): void
    {
        foreach (LoanStatus::reserving() as $status) {
            $loan = $this->loan(3, '2026-10-21', '2026-10-23', $status);
            $this->assertSame(0, $this->svc->availableQty($this->item, '2026-10-21', '2026-10-23'), $status->value);
            $loan->forceDelete();
        }
    }

    public function test_unit_selain_available_tidak_dihitung(): void
    {
        ItemUnit::where('item_id', $this->item->id)->first()->update(['status' => 'maintenance']);

        $this->assertSame(2, $this->svc->availableQty($this->item, '2026-10-21', '2026-10-23'));
    }

    public function test_dapat_mengecualikan_loan_tertentu(): void
    {
        $loan = $this->loan(3, '2026-10-21', '2026-10-23');

        $this->assertSame(0, $this->svc->availableQty($this->item, '2026-10-21', '2026-10-23'));
        $this->assertSame(3, $this->svc->availableQty($this->item, '2026-10-21', '2026-10-23', $loan->id));
    }

    public function test_is_available_memeriksa_batas_jumlah(): void
    {
        $this->loan(2, '2026-10-21', '2026-10-21');

        $this->assertTrue($this->svc->isAvailable($this->item, 1, '2026-10-21', '2026-10-21'));
        $this->assertFalse($this->svc->isAvailable($this->item, 2, '2026-10-21', '2026-10-21'));
        $this->assertFalse($this->svc->isAvailable($this->item, 0, '2026-10-21', '2026-10-21'));
    }

    public function test_tanggal_penuh_untuk_kalender(): void
    {
        $single = Item::factory()->withUnits(1)->create();
        Loan::factory()->between('2026-10-21', '2026-10-22')->create(['item_id' => $single->id, 'quantity' => 1]);

        $full = $this->svc->fullDates($single, '2026-10-20', '2026-10-24');

        $this->assertSame(['2026-10-21', '2026-10-22'], $full);
    }

    public function test_siap_hari_ini_untuk_banner(): void
    {
        $this->loan(2, '2026-10-19', '2026-10-20'); // menahan 2 dari 3 unit hari ini

        $this->assertSame(1, $this->svc->readyOn('2026-10-19'));
    }
}
