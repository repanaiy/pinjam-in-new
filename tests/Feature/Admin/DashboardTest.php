<?php

namespace Tests\Feature\Admin;

use App\Enums\LoanStatus;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Loan;
use App\Models\LoanUnit;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-19 09:00:00'));

        $this->admin = User::factory()->admin()->create();
        $this->item = Item::factory()->withUnits(4)->create(['name' => 'Kamera Uji', 'min_stock' => 2]);
        ItemUnit::factory()->create(['item_id' => $this->item->id, 'status' => 'inactive']); // tidak dihitung
    }

    private function borrowedLoan(): Loan
    {
        $loan = Loan::factory()->status(LoanStatus::Borrowed)->between('2026-10-17', '2026-10-19')->create([
            'item_id' => $this->item->id, 'quantity' => 1, 'borrowed_at' => now()->subDay(),
        ]);
        LoanUnit::create(['loan_id' => $loan->id, 'item_unit_id' => $this->item->units()->orderBy('id')->first()->id, 'condition_out' => 'baik']);

        return $loan;
    }

    public function test_kartu_ringkasan_menghitung_unit_dan_pengajuan(): void
    {
        $this->borrowedLoan();
        Loan::factory()->between('2026-10-20', '2026-10-21')->create(['item_id' => $this->item->id, 'quantity' => 2]);

        $s = app(DashboardService::class)->summary();

        $this->assertSame(4, $s['totalUnits'], 'unit nonaktif tidak dihitung');
        $this->assertSame(3, $s['availableUnits'], 'unit yang sedang dibawa siswa tidak dihitung tersedia');
        $this->assertSame(1, $s['pending']);
        $this->assertSame(1, $s['borrowed']);
        $this->assertCount(7, $s['spark']['pending']);
        $this->assertSame(3, end($s['spark']['available']));
    }

    public function test_grafik_7_hari_dipinjam_vs_dikembalikan(): void
    {
        $this->borrowedLoan(); // dipinjam kemarin (indeks 5)
        Loan::factory()->status(LoanStatus::Returned)->create([
            'item_id' => $this->item->id, 'borrowed_at' => now()->subDays(3), 'returned_at' => now(),
        ]);

        $chart = app(DashboardService::class)->summary()['chart'];

        $this->assertCount(7, $chart['labels']);
        $this->assertSame(1, $chart['borrowed'][5]);
        $this->assertSame(1, $chart['borrowed'][3]);
        $this->assertSame(1, $chart['returned'][6]);
        $this->assertSame('19 Okt', $chart['labels'][6]);
    }

    public function test_panel_perlu_perhatian(): void
    {
        $this->borrowedLoan(); // jatuh tempo hari ini
        Loan::factory()->status(LoanStatus::Overdue)->between('2026-10-12', '2026-10-15')->create(['item_id' => $this->item->id, 'quantity' => 1]);
        Loan::factory()->between('2026-10-19', '2026-10-20')->create(['item_id' => $this->item->id, 'quantity' => 2]);

        $a = app(DashboardService::class)->summary()['attention'];

        $this->assertCount(1, $a['dueToday']);
        $this->assertSame(1, $a['overdue']);
        $this->assertSame(0, $a['openDamage']);
        // 4 unit − (1 borrowed + 2 pending) = 1 ≤ min_stock 2
        $this->assertCount(1, $a['lowStock']);
        $this->assertSame(1, $a['lowStock'][0]['ready']);
    }

    public function test_stok_aman_tidak_masuk_stok_minimum(): void
    {
        $a = app(DashboardService::class)->summary()['attention'];

        $this->assertTrue($a['lowStock']->isEmpty());
    }

    public function test_utilisasi_dalam_rentang_0_sampai_100(): void
    {
        $this->assertSame(0, app(DashboardService::class)->summary()['utilization']);

        $this->borrowedLoan(); // 2 unit-hari / (4 unit × 30 hari)
        $u = app(DashboardService::class)->summary()['utilization'];

        $this->assertGreaterThan(0, $u);
        $this->assertLessThanOrEqual(100, $u);
    }

    public function test_antrean_hanya_pending_dan_reviewing_terlama_dulu(): void
    {
        Loan::factory()->create(['item_id' => $this->item->id, 'request_code' => 'REQ-2026-3001', 'created_at' => now()->subDays(2)]);
        Loan::factory()->status(LoanStatus::Reviewing)->create(['item_id' => $this->item->id, 'request_code' => 'REQ-2026-3002', 'created_at' => now()->subDays(5)]);
        Loan::factory()->status(LoanStatus::Approved)->create(['item_id' => $this->item->id, 'request_code' => 'REQ-2026-3003']);

        $queue = app(DashboardService::class)->summary()['queue'];

        $this->assertSame(['REQ-2026-3002', 'REQ-2026-3001'], $queue->pluck('request_code')->all());
    }

    public function test_halaman_dashboard_tampil_untuk_admin(): void
    {
        $this->borrowedLoan();
        Loan::factory()->create(['item_id' => $this->item->id, 'request_code' => 'REQ-2026-3010']);

        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Sarpras')
            ->assertSee('Aktivitas')
            ->assertSee('Perlu perhatian')
            ->assertSee('Utilisasi 30 hari')
            ->assertSee('Antrean verifikasi')
            ->assertSee('Tinjau')
            ->assertSee('Tambah Item');
    }

    public function test_dashboard_kosong_tidak_error(): void
    {
        ItemUnit::query()->withTrashed()->forceDelete();
        Item::query()->withTrashed()->forceDelete();

        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertOk()->assertSee('Tidak ada pengajuan menunggu');
    }

    public function test_siswa_tidak_bisa_membuka_dashboard(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();
    }
}
