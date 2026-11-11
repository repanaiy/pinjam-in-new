<?php

namespace Tests\Feature\Admin;

use App\Enums\LoanStatus;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Loan;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VerifikasiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $siswa;

    private Item $item;

    private Loan $loan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-19 09:00:00')); // Senin

        $this->admin = User::factory()->admin()->create();
        $this->siswa = User::factory()->create(['name' => 'Raka Pratama']);
        $this->item = Item::factory()->withUnits(3)->create(['name' => 'Kamera Uji', 'location' => 'Ruang Sarpras']);
        $this->loan = Loan::factory()->between('2026-10-21', '2026-10-23')->create([
            'user_id' => $this->siswa->id, 'item_id' => $this->item->id, 'quantity' => 1,
        ]);
    }

    private function approvePayload(array $o = []): array
    {
        return array_merge([
            'pickup_date' => '2026-10-21',
            'pickup_time_start' => '09:00',
            'pickup_time_end' => '11:00',
            'pickup_location' => 'Ruang Sarpras, Gedung B',
            'admin_note' => 'Bawa kartu pelajar asli',
        ], $o);
    }

    private function free(): int
    {
        return app(AvailabilityService::class)->availableQty($this->item, '2026-10-21', '2026-10-23');
    }

    public function test_membuka_detail_mengubah_pending_menjadi_reviewing(): void
    {
        $this->actingAs($this->admin)->get(route('admin.verifikasi.show', $this->loan))
            ->assertOk()
            ->assertSee('Siswa aktif')
            ->assertSee('Hasil cek otomatis')
            ->assertSee('Identitas sesuai dan jadwal tidak bentrok');

        $this->loan->refresh();
        $this->assertSame(LoanStatus::Reviewing, $this->loan->status);
        $this->assertSame($this->admin->id, $this->loan->reviewed_by);
        $this->assertDatabaseHas('activity_logs', ['loan_id' => $this->loan->id, 'action' => 'reviewing']);
    }

    public function test_admin_kedua_terkunci_sampai_mengambil_alih(): void
    {
        $admin2 = User::factory()->admin()->create(['name' => 'Admin Dua']);

        $this->actingAs($this->admin)->get(route('admin.verifikasi.show', $this->loan));

        $this->actingAs($admin2)->get(route('admin.verifikasi.show', $this->loan))
            ->assertOk()->assertSee('Sedang ditinjau admin lain');

        $this->actingAs($admin2)->post(route('admin.verifikasi.approve', $this->loan), $this->approvePayload())
            ->assertSessionHas('toast.title', 'Sedang ditinjau admin lain');
        $this->assertSame(LoanStatus::Reviewing, $this->loan->fresh()->status);

        $this->actingAs($admin2)->post(route('admin.verifikasi.takeover', $this->loan));
        $this->assertSame($admin2->id, $this->loan->fresh()->reviewed_by);

        $this->actingAs($admin2)->post(route('admin.verifikasi.approve', $this->loan), $this->approvePayload())
            ->assertSessionHasNoErrors();
        $this->assertSame(LoanStatus::Approved, $this->loan->fresh()->status);
    }

    public function test_setujui_menyimpan_jadwal_mencatat_log_dan_memberi_notifikasi(): void
    {
        $this->actingAs($this->admin)->post(route('admin.verifikasi.approve', $this->loan), $this->approvePayload())
            ->assertRedirect(route('admin.verifikasi.show', $this->loan));

        $loan = $this->loan->fresh();
        $this->assertSame(LoanStatus::Approved, $loan->status);
        $this->assertNotNull($loan->approved_at);
        $this->assertSame('2026-10-21', $loan->pickup_date->toDateString());
        $this->assertSame('Ruang Sarpras, Gedung B', $loan->pickup_location);
        $this->assertSame('Bawa kartu pelajar asli', $loan->admin_note);
        $this->assertSame($this->admin->id, $loan->reviewed_by);

        $this->assertDatabaseHas('activity_logs', ['loan_id' => $loan->id, 'action' => 'approved', 'actor_id' => $this->admin->id]);
        $notif = $this->siswa->notifications()->first();
        $this->assertSame('approved', $notif->data['kind']);
        $this->assertStringContainsString('Ruang Sarpras, Gedung B', $notif->data['message']);
        $this->assertSame(2, $this->free(), 'reservasi 1 unit tetap menahan stok setelah disetujui');
    }

    public function test_validasi_jadwal_pengambilan(): void
    {
        $url = route('admin.verifikasi.approve', $this->loan);
        $this->actingAs($this->admin);

        $this->post($url, $this->approvePayload(['pickup_date' => '2026-10-16']))->assertSessionHasErrors('pickup_date');   // lampau
        $this->post($url, $this->approvePayload(['pickup_date' => '2026-10-26']))->assertSessionHasErrors('pickup_date');   // lewat tanggal kembali
        $this->post($url, $this->approvePayload(['pickup_time_start' => '06:00']))->assertSessionHasErrors('pickup_time_start');
        $this->post($url, $this->approvePayload(['pickup_time_end' => '16:00']))->assertSessionHasErrors('pickup_time_end');
        $this->post($url, $this->approvePayload(['pickup_time_start' => '11:00', 'pickup_time_end' => '10:00']))->assertSessionHasErrors('pickup_time_end');
        $this->post($url, $this->approvePayload(['pickup_location' => '']))->assertSessionHasErrors('pickup_location');

        $this->assertSame(LoanStatus::Pending, $this->loan->fresh()->status);
    }

    public function test_tanggal_pengambilan_tidak_boleh_akhir_pekan(): void
    {
        $loan = Loan::factory()->between('2026-10-23', '2026-10-27')->create(['item_id' => $this->item->id]);

        $this->actingAs($this->admin)
            ->post(route('admin.verifikasi.approve', $loan), $this->approvePayload(['pickup_date' => '2026-10-24'])) // Sabtu
            ->assertSessionHasErrors('pickup_date');
    }

    public function test_stok_dicek_ulang_saat_menyetujui(): void
    {
        $this->loan->update(['quantity' => 3]);
        ItemUnit::where('item_id', $this->item->id)->first()->update(['status' => 'maintenance']); // tinggal 2 unit

        $this->actingAs($this->admin)->post(route('admin.verifikasi.approve', $this->loan), $this->approvePayload())
            ->assertSessionHas('toast.title', 'Stok tidak mencukupi');

        $this->assertSame(LoanStatus::Pending, $this->loan->fresh()->status);
    }

    public function test_tolak_wajib_alasan_melepas_reservasi_dan_memberi_tahu_siswa(): void
    {
        $this->actingAs($this->admin);
        $this->assertSame(2, $this->free());

        $this->post(route('admin.verifikasi.reject', $this->loan), ['reject_reason' => ''])->assertSessionHasErrors('reject_reason');
        $this->post(route('admin.verifikasi.reject', $this->loan), ['reject_reason' => 'abc'])->assertSessionHasErrors('reject_reason');
        $this->assertSame(LoanStatus::Pending, $this->loan->fresh()->status);

        $this->post(route('admin.verifikasi.reject', $this->loan), ['reject_reason' => 'Jadwal bentrok dengan kegiatan sekolah.'])
            ->assertSessionHasNoErrors();

        $loan = $this->loan->fresh();
        $this->assertSame(LoanStatus::Rejected, $loan->status);
        $this->assertSame('Jadwal bentrok dengan kegiatan sekolah.', $loan->reject_reason);
        $this->assertSame(3, $this->free(), 'reservasi lepas otomatis');
        $this->assertSame('rejected', $this->siswa->notifications()->first()->data['kind']);
        $this->assertStringContainsString('Jadwal bentrok', $this->siswa->notifications()->first()->data['message']);
        $this->assertDatabaseHas('activity_logs', ['loan_id' => $loan->id, 'action' => 'rejected']);
    }

    public function test_pengajuan_yang_sudah_dibatalkan_tidak_bisa_diputuskan(): void
    {
        $this->loan->update(['status' => LoanStatus::Cancelled]);

        $this->actingAs($this->admin)->post(route('admin.verifikasi.approve', $this->loan), $this->approvePayload())
            ->assertSessionHas('toast.title', 'Status sudah berubah');
        $this->actingAs($this->admin)->post(route('admin.verifikasi.reject', $this->loan), ['reject_reason' => 'Alasan apa pun'])
            ->assertSessionHas('toast.title', 'Status sudah berubah');

        $this->assertSame(LoanStatus::Cancelled, $this->loan->fresh()->status);
    }

    public function test_tandai_siap_diambil(): void
    {
        $this->loan->update(['status' => LoanStatus::Approved, 'pickup_location' => 'Ruang Sarpras']);

        $this->actingAs($this->admin)->post(route('admin.verifikasi.ready', $this->loan))->assertRedirect();

        $this->assertSame(LoanStatus::ReadyPickup, $this->loan->fresh()->status);
        $this->assertDatabaseHas('activity_logs', ['loan_id' => $this->loan->id, 'action' => 'ready_pickup']);
        $this->assertSame('ready_pickup', $this->siswa->notifications()->first()->data['kind']);

        // Tidak bisa diulang / dari status lain.
        $this->actingAs($this->admin)->post(route('admin.verifikasi.ready', $this->loan))
            ->assertSessionHas('toast.title', 'Status sudah berubah');
    }

    public function test_periksa_ketersediaan_dan_kelayakan(): void
    {
        $this->actingAs($this->admin)->post(route('admin.verifikasi.check', $this->loan))
            ->assertSessionHas('toast.title', 'Layak disetujui');

        $this->siswa->update(['is_active' => false]);
        $this->actingAs($this->admin)->post(route('admin.verifikasi.check', $this->loan))
            ->assertSessionHas('toast.title', 'Perlu diperhatikan');
    }

    public function test_antrean_cari_filter_status_dan_urutan(): void
    {
        $budi = User::factory()->create(['name' => 'Budi Santoso']);
        $citra = User::factory()->create(['name' => 'Citra Dewi']);
        Loan::factory()->create(['user_id' => $budi->id, 'item_id' => $this->item->id, 'request_code' => 'REQ-2026-2001', 'created_at' => now()->subDays(3)]);
        Loan::factory()->create(['user_id' => $citra->id, 'item_id' => $this->item->id, 'request_code' => 'REQ-2026-2002', 'created_at' => now()->subDay()]);
        Loan::factory()->status(LoanStatus::Returned)->create(['user_id' => $citra->id, 'item_id' => $this->item->id, 'request_code' => 'REQ-2026-2003']);

        $this->actingAs($this->admin);

        $this->get(route('admin.verifikasi.index'))->assertOk()
            ->assertSee('REQ-2026-2001')->assertSee('REQ-2026-2002')->assertDontSee('REQ-2026-2003');
        $this->get(route('admin.verifikasi.index', ['q' => 'Budi']))
            ->assertSee('REQ-2026-2001')->assertDontSee('REQ-2026-2002');
        $this->get(route('admin.verifikasi.index', ['q' => 'REQ-2026-2002']))
            ->assertSee('Citra Dewi')->assertDontSee('Budi Santoso');
        $this->get(route('admin.verifikasi.index', ['status' => 'returned']))
            ->assertSee('REQ-2026-2003')->assertDontSee('REQ-2026-2001');
        $this->get(route('admin.verifikasi.index', ['urut' => 'terlama']))
            ->assertSeeInOrder(['REQ-2026-2001', 'REQ-2026-2002']);
        $this->get(route('admin.verifikasi.index', ['urut' => 'terbaru']))
            ->assertSeeInOrder(['REQ-2026-2002', 'REQ-2026-2001']);
    }

    public function test_hanya_admin_yang_boleh_masuk(): void
    {
        $this->actingAs($this->siswa)->get(route('admin.verifikasi.index'))->assertForbidden();
        $this->actingAs($this->siswa)->get(route('admin.verifikasi.show', $this->loan))->assertForbidden();
        $this->actingAs($this->siswa)->post(route('admin.verifikasi.approve', $this->loan), $this->approvePayload())->assertForbidden();
        $this->actingAs($this->siswa)->post(route('admin.verifikasi.reject', $this->loan), ['reject_reason' => 'Coba-coba'])->assertForbidden();
        $this->assertSame(LoanStatus::Pending, $this->loan->fresh()->status);
    }

    public function test_admin_dapat_melihat_berkas_privat_siswa_dari_halaman_detail(): void
    {
        $this->actingAs($this->admin)->get(route('admin.verifikasi.show', $this->loan))
            ->assertSee(route('berkas.kartu', $this->loan), false);
    }
}
