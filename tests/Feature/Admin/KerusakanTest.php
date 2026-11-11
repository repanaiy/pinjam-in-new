<?php

namespace Tests\Feature\Admin;

use App\Enums\DamageStatus;
use App\Enums\LoanStatus;
use App\Models\DamageReport;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class KerusakanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ItemUnit $unit;

    private Loan $loan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-21 10:00:00'));

        $this->admin = User::factory()->admin()->create();
        $item = Item::factory()->withUnits(2)->create(['name' => 'Kamera Rusak']);
        $this->unit = $item->units()->orderBy('id')->first();
        $this->unit->update(['status' => 'maintenance']);
        $this->loan = Loan::factory()->status(LoanStatus::ReturnedDamaged)->create(['item_id' => $item->id]);
    }

    private function report(string $status = 'open', string $type = 'rusak'): DamageReport
    {
        return DamageReport::create([
            'loan_id' => $this->loan->id, 'item_unit_id' => $this->unit->id, 'type' => $type,
            'description' => 'Lensa retak', 'status' => $status, 'reported_by' => $this->admin->id,
        ]);
    }

    private function update(DamageReport $r, array $data)
    {
        return $this->actingAs($this->admin)->patch(route('admin.inventaris.kerusakan.update', $r), $data);
    }

    public function test_open_ke_in_repair_ke_resolved_mengembalikan_unit_tersedia(): void
    {
        $r = $this->report();

        $this->update($r, ['status' => 'in_repair'])->assertSessionHas('toast.type', 'success');
        $this->assertSame(DamageStatus::InRepair, $r->fresh()->status);
        $this->assertSame('maintenance', $this->unit->fresh()->status->value);
        $this->assertNull($r->fresh()->resolved_at);

        $this->update($r, ['status' => 'resolved', 'resolution_note' => 'Lensa diganti di service center'])->assertSessionHas('toast.type', 'success');
        $r->refresh();
        $this->assertSame(DamageStatus::Resolved, $r->status);
        $this->assertSame('Lensa diganti di service center', $r->resolution_note);
        $this->assertNotNull($r->resolved_at);
        $this->assertSame('available', $this->unit->fresh()->status->value, 'unit kembali tersedia');
        $this->assertDatabaseHas('activity_logs', ['loan_id' => $this->loan->id, 'action' => 'damage_resolved', 'actor_id' => $this->admin->id]);
    }

    public function test_written_off_menonaktifkan_unit(): void
    {
        $r = $this->report('in_repair');

        $this->update($r, ['status' => 'written_off', 'resolution_note' => 'Tidak ekonomis diperbaiki'])->assertSessionHas('toast.type', 'success');

        $this->assertSame(DamageStatus::WrittenOff, $r->fresh()->status);
        $this->assertSame('inactive', $this->unit->fresh()->status->value);
        $this->assertNotNull($r->fresh()->resolved_at);
    }

    public function test_open_boleh_langsung_dihapusbukukan(): void
    {
        $r = $this->report('open', 'hilang');
        $this->unit->update(['status' => 'lost']);

        $this->update($r, ['status' => 'written_off', 'resolution_note' => 'Dinyatakan hilang permanen'])->assertSessionHas('toast.type', 'success');

        $this->assertSame('inactive', $this->unit->fresh()->status->value);
    }

    public function test_transisi_ilegal_ditolak(): void
    {
        $open = $this->report('open');
        $this->update($open, ['status' => 'resolved', 'resolution_note' => 'Langsung selesai'])->assertSessionHas('toast.title', 'Status tidak diubah');
        $this->assertSame(DamageStatus::Open, $open->fresh()->status);
        $this->assertSame('maintenance', $this->unit->fresh()->status->value);

        foreach (['resolved', 'written_off'] as $final) {
            $r = $this->report($final);
            $this->update($r, ['status' => 'in_repair'])->assertSessionHas('toast.title', 'Status tidak diubah');
            $this->assertSame(DamageStatus::from($final), $r->fresh()->status);
        }
    }

    public function test_catatan_wajib_saat_selesai_atau_dihapusbukukan(): void
    {
        $r = $this->report('in_repair');

        $this->update($r, ['status' => 'resolved'])->assertSessionHas('toast.title', 'Status tidak diubah');
        $this->update($r, ['status' => 'written_off', 'resolution_note' => '  '])->assertSessionHas('toast.title', 'Status tidak diubah');

        $this->assertSame(DamageStatus::InRepair, $r->fresh()->status);
    }

    public function test_status_tidak_valid_ditolak_validasi(): void
    {
        $r = $this->report();

        $this->update($r, ['status' => 'open'])->assertSessionHasErrors('status');
        $this->update($r, ['status' => 'ngawur'])->assertSessionHasErrors('status');
        $this->update($r, [])->assertSessionHasErrors('status');
    }

    public function test_daftar_menyaring_status(): void
    {
        $open = $this->report('open');
        $done = $this->report('resolved');
        $done->update(['description' => 'Sudah selesai diperbaiki']);

        $this->actingAs($this->admin);
        $this->get(route('admin.inventaris.kerusakan.index'))->assertOk()->assertSee('Lensa retak')->assertDontSee('Sudah selesai diperbaiki');
        $this->get(route('admin.inventaris.kerusakan.index', ['status' => 'resolved']))->assertSee('Sudah selesai diperbaiki')->assertDontSee('Lensa retak');
        $this->get(route('admin.inventaris.kerusakan.index', ['status' => 'semua']))->assertSee('Lensa retak')->assertSee('Sudah selesai diperbaiki');
        $this->assertSame(DamageStatus::Open, $open->fresh()->status);
    }

    public function test_keadaan_kosong_dan_hak_akses(): void
    {
        $this->actingAs($this->admin)->get(route('admin.inventaris.kerusakan.index'))->assertOk()->assertSee('Tidak ada laporan');

        $r = $this->report();
        $this->actingAs(User::factory()->create())->get(route('admin.inventaris.kerusakan.index'))->assertForbidden();
        $this->actingAs(User::factory()->create())->patch(route('admin.inventaris.kerusakan.update', $r), ['status' => 'in_repair'])->assertForbidden();
        $this->assertSame(DamageStatus::Open, $r->fresh()->status);
    }
}
