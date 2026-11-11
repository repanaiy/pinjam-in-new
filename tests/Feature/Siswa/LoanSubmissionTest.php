<?php

namespace Tests\Feature\Siswa;

use App\Models\ActivityLog;
use App\Models\Holiday;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LoanSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private User $siswa;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo(Carbon::parse('2026-10-19 09:00:00')); // Senin

        $this->siswa = User::factory()->create();
        $this->item = Item::factory()->withUnits(3)->create(['max_qty_per_loan' => 2, 'max_loan_days' => 7]);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'quantity' => 1,
            'start_date' => '2026-10-21', // Rabu
            'end_date' => '2026-10-23',   // Jumat
            'purpose' => 'Dokumentasi kegiatan OSIS',
            'student_card' => UploadedFile::fake()->create('kartu.pdf', 100, 'application/pdf'),
            'agreement_accepted' => '1',
        ], $override);
    }

    private function submit(array $override = [], ?User $as = null, ?Item $item = null)
    {
        return $this->actingAs($as ?? $this->siswa)
            ->post(route('pengajuan.store', $item ?? $this->item), $this->payload($override));
    }

    public function test_pengajuan_berhasil_membuat_loan_pending_log_dan_notifikasi(): void
    {
        $response = $this->submit();

        $loan = Loan::firstOrFail();
        $response->assertRedirect(route('pengajuan.show', $loan));

        $this->assertSame('pending', $loan->status->value);
        $this->assertStringStartsWith('REQ-2026-', $loan->request_code);
        $this->assertTrue($loan->agreement_accepted);
        Storage::disk('local')->assertExists($loan->student_card_path);
        $this->assertDatabaseHas('activity_logs', ['loan_id' => $loan->id, 'action' => 'submitted', 'actor_id' => $this->siswa->id]);
        $this->assertSame(1, $this->siswa->notifications()->count());
    }

    public function test_berkas_tersimpan_privat_bukan_di_disk_public(): void
    {
        $this->submit();

        $this->assertStringStartsWith('student-cards/', Loan::first()->student_card_path);
        $this->assertFalse(str_contains(Loan::first()->student_card_path, 'kartu.pdf'), 'nama berkas harus di-hash');
    }

    public function test_stok_habis_pada_tanggal_dipilih_menolak_dan_tidak_menyimpan_apa_pun(): void
    {
        Loan::factory()->between('2026-10-22', '2026-10-22')->create(['item_id' => $this->item->id, 'quantity' => 3]);

        $this->submit()->assertSessionHas('unavailable');

        $this->assertSame(1, Loan::count(), 'hanya loan yang sudah ada');
        $this->assertSame([], Storage::disk('local')->allFiles(), 'tidak ada berkas yatim');
        $this->assertSame(0, ActivityLog::where('action', 'submitted')->count());
    }

    public function test_dua_pengajuan_berurutan_untuk_stok_terakhir_hanya_satu_berhasil(): void
    {
        $single = Item::factory()->withUnits(1)->create(['max_qty_per_loan' => 1]);
        $lain = User::factory()->create();

        $this->submit([], $this->siswa, $single)->assertSessionMissing('unavailable');
        $this->submit([], $lain, $single)->assertSessionHas('unavailable');

        $this->assertSame(1, Loan::where('item_id', $single->id)->count());
    }

    public function test_validasi_akhir_pekan_hari_libur_dan_masa_lalu(): void
    {
        Holiday::create(['date' => '2026-10-22', 'name' => 'Libur uji']);

        $this->submit(['start_date' => '2026-10-24', 'end_date' => '2026-10-27'])->assertSessionHasErrors('start_date');
        $this->submit(['start_date' => '2026-10-21', 'end_date' => '2026-10-22'])->assertSessionHasErrors('end_date');
        $this->submit(['start_date' => '2026-10-16', 'end_date' => '2026-10-16'])->assertSessionHasErrors('start_date');
        $this->assertSame(0, Loan::count());
    }

    public function test_validasi_durasi_maksimal_dan_urutan_tanggal(): void
    {
        $this->submit(['start_date' => '2026-10-21', 'end_date' => '2026-10-29'])->assertSessionHasErrors('end_date'); // 9 hari
        $this->submit(['start_date' => '2026-10-23', 'end_date' => '2026-10-21'])->assertSessionHasErrors('end_date');
        $this->assertSame(0, Loan::count());
    }

    public function test_validasi_jumlah_melebihi_batas_per_peminjaman(): void
    {
        $this->submit(['quantity' => 3])->assertSessionHasErrors('quantity');
        $this->submit(['quantity' => 0])->assertSessionHasErrors('quantity');
    }

    public function test_validasi_berkas_kartu_pelajar(): void
    {
        $this->submit(['student_card' => UploadedFile::fake()->create('kartu.exe', 10, 'application/x-msdownload')])
            ->assertSessionHasErrors('student_card');
        $this->submit(['student_card' => UploadedFile::fake()->create('kartu.pdf', 6000, 'application/pdf')])
            ->assertSessionHasErrors('student_card');
        $this->submit(['student_card' => null])->assertSessionHasErrors('student_card');
        $this->assertSame(0, Loan::count());
    }

    public function test_pernyataan_tanggung_jawab_dan_keperluan_wajib(): void
    {
        $this->submit(['agreement_accepted' => null])->assertSessionHasErrors('agreement_accepted');
        $this->submit(['purpose' => 'singkat'])->assertSessionHasErrors('purpose');
    }

    public function test_alat_nonaktif_tidak_bisa_diajukan(): void
    {
        $off = Item::factory()->inactive()->withUnits(1)->create();

        $this->submit([], null, $off)->assertNotFound();
        $this->actingAs($this->siswa)->get(route('katalog.show', $off))->assertNotFound();
    }

    public function test_admin_tidak_bisa_mengajukan(): void
    {
        $this->submit([], User::factory()->admin()->create())->assertForbidden();
    }
}
