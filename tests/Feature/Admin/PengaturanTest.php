<?php

namespace Tests\Feature\Admin;

use App\Models\Holiday;
use App\Models\Item;
use App\Models\Loan;
use App\Models\Setting;
use App\Models\User;
use App\Services\ServiceCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PengaturanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo(Carbon::parse('2026-10-19 09:00:00')); // Senin
        $this->admin = User::factory()->admin()->create();
    }

    private function payload(array $o = []): array
    {
        return array_merge([
            'service_days' => [1, 2, 3, 4, 5],
            'service_hour_start' => '07:00',
            'service_hour_end' => '15:00',
            'max_loan_days' => 7,
            'review_sla_workdays' => 1,
            'pickup_grace_workdays' => 1,
            'late_warning_threshold' => 3,
            'academic_year' => '2026/2027',
            'semester_label' => 'Semester ganjil 2026',
        ], $o);
    }

    private function save(array $o = [])
    {
        return $this->actingAs($this->admin)->put(route('admin.pengaturan.update'), $this->payload($o));
    }

    private function submitLoan(User $siswa, Item $item, string $start, string $end)
    {
        return $this->actingAs($siswa)->post(route('pengajuan.store', $item), [
            'quantity' => 1, 'start_date' => $start, 'end_date' => $end,
            'purpose' => 'Dokumentasi kegiatan OSIS',
            'student_card' => UploadedFile::fake()->create('kartu.pdf', 10, 'application/pdf'),
            'agreement_accepted' => '1',
        ]);
    }

    public function test_halaman_menampilkan_nilai_saat_ini(): void
    {
        Setting::put('semester_label', 'Semester uji 2026');

        $this->actingAs($this->admin)->get(route('admin.pengaturan.index'))
            ->assertOk()->assertSee('Hari layanan')->assertSee('Semester uji 2026')->assertSee('Hari libur');
    }

    public function test_simpan_pengaturan(): void
    {
        $this->save([
            'service_days' => [1, 2, 3, 4, 6], 'service_hour_start' => '08:00', 'service_hour_end' => '14:30',
            'max_loan_days' => 5, 'review_sla_workdays' => 2, 'pickup_grace_workdays' => 0, 'late_warning_threshold' => 0,
            'academic_year' => '2027/2028', 'semester_label' => 'Semester genap 2027',
        ])->assertSessionHasNoErrors()->assertSessionHas('toast.title', 'Pengaturan disimpan');

        $this->assertSame('1,2,3,4,6', Setting::get('service_days'));
        $this->assertSame('08:00', Setting::get('service_hour_start'));
        $this->assertSame('14:30', Setting::get('service_hour_end'));
        $this->assertSame('5', Setting::get('max_loan_days'));
        $this->assertSame('2', Setting::get('review_sla_workdays'));
        $this->assertSame('0', Setting::get('pickup_grace_workdays'));
        $this->assertSame('0', Setting::get('late_warning_threshold'));
        $this->assertSame('2027/2028', Setting::get('academic_year'));
        $this->assertSame([1, 2, 3, 4, 6], app(ServiceCalendar::class)->serviceDays());
        $this->assertDatabaseHas('activity_logs', ['action' => 'settings_updated', 'actor_id' => $this->admin->id]);
    }

    public function test_hari_layanan_dinormalkan_urut_dan_unik(): void
    {
        $this->save(['service_days' => [5, 1, 1, 3]])->assertSessionHasNoErrors();

        $this->assertSame('1,3,5', Setting::get('service_days'));
    }

    public function test_validasi_pengaturan(): void
    {
        $this->save(['service_days' => []])->assertSessionHasErrors('service_days');
        $this->save(['service_days' => [8]])->assertSessionHasErrors('service_days.0');
        $this->save(['service_hour_start' => '15:00', 'service_hour_end' => '07:00'])->assertSessionHasErrors('service_hour_end');
        $this->save(['service_hour_start' => '25:00'])->assertSessionHasErrors('service_hour_start');
        $this->save(['max_loan_days' => 0])->assertSessionHasErrors('max_loan_days');
        $this->save(['max_loan_days' => 31])->assertSessionHasErrors('max_loan_days');
        $this->save(['review_sla_workdays' => 0])->assertSessionHasErrors('review_sla_workdays');
        $this->save(['pickup_grace_workdays' => -1])->assertSessionHasErrors('pickup_grace_workdays');
        $this->save(['late_warning_threshold' => 21])->assertSessionHasErrors('late_warning_threshold');
        $this->save(['academic_year' => '2026-2027'])->assertSessionHasErrors('academic_year');
        $this->save(['semester_label' => ''])->assertSessionHasErrors('semester_label');

        $this->assertNull(Setting::get('academic_year'), 'tidak ada yang tersimpan saat validasi gagal');
    }

    public function test_tahun_ajaran_dan_semester_tampil_di_layout(): void
    {
        $this->save(['academic_year' => '2027/2028', 'semester_label' => 'Semester genap 2027']);

        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertSee('Tahun ajaran 2027/2028')->assertSee('Semester genap 2027');
    }

    public function test_menambah_hari_sabtu_mengizinkan_pengajuan_di_hari_sabtu(): void
    {
        $item = Item::factory()->withUnits(2)->create(['max_qty_per_loan' => 1]);
        $siswa = User::factory()->create();

        $this->submitLoan($siswa, $item, '2026-10-24', '2026-10-24')->assertSessionHasErrors('start_date'); // Sabtu: bukan hari layanan
        $this->assertSame(0, Loan::count());

        $this->save(['service_days' => [1, 2, 3, 4, 5, 6]]);

        $this->submitLoan($siswa, $item, '2026-10-24', '2026-10-24')->assertSessionHasNoErrors();
        $this->assertSame(1, Loan::count());
    }

    public function test_menghapus_hari_jumat_dari_layanan_menolak_pengajuan_di_hari_jumat(): void
    {
        $item = Item::factory()->withUnits(2)->create(['max_qty_per_loan' => 1]);
        $siswa = User::factory()->create();

        $this->save(['service_days' => [1, 2, 3, 4]]);

        $this->submitLoan($siswa, $item, '2026-10-23', '2026-10-23')->assertSessionHasErrors('start_date'); // Jumat
        $this->submitLoan($siswa, $item, '2026-10-22', '2026-10-22')->assertSessionHasNoErrors();
    }

    public function test_jam_layanan_membatasi_jadwal_pengambilan(): void
    {
        $item = Item::factory()->withUnits(1)->create();
        $loan = Loan::factory()->between('2026-10-21', '2026-10-23')->create(['item_id' => $item->id]);
        $approve = fn () => $this->actingAs($this->admin)->post(route('admin.verifikasi.approve', $loan), [
            'pickup_date' => '2026-10-21', 'pickup_time_start' => '13:00', 'pickup_time_end' => '13:30', 'pickup_location' => 'Ruang Sarpras',
        ]);

        $this->save(['service_hour_end' => '12:00']);
        $approve()->assertSessionHasErrors('pickup_time_start');

        $this->save(['service_hour_end' => '15:00']);
        $approve()->assertSessionHasNoErrors();
    }

    public function test_ambang_peringatan_keterlambatan(): void
    {
        $item = Item::factory()->withUnits(1)->create();
        $siswa = User::factory()->create(['late_count' => 2]);

        $this->save(['late_warning_threshold' => 3]);
        $this->actingAs($siswa)->get(route('katalog.show', $item))->assertDontSee('Riwayat keterlambatan');

        $this->save(['late_warning_threshold' => 2]);
        $this->actingAs($siswa)->get(route('katalog.show', $item))->assertSee('Riwayat keterlambatan');

        $this->save(['late_warning_threshold' => 0]);
        $this->actingAs($siswa)->get(route('katalog.show', $item))->assertDontSee('Riwayat keterlambatan');
    }

    // ------------------------------------------------------------------ hari libur

    public function test_tambah_dan_hapus_hari_libur(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('admin.pengaturan.libur.store'), ['date' => '2026-10-22', 'name' => 'Libur uji'])->assertSessionHasNoErrors();
        $h = Holiday::firstOrFail();
        $this->assertSame('2026-10-22', $h->date->toDateString());
        $this->assertDatabaseHas('activity_logs', ['action' => 'holiday_created']);

        $this->get(route('admin.pengaturan.index'))->assertSee('Libur uji');

        $this->delete(route('admin.pengaturan.libur.destroy', $h))->assertSessionHas('toast.title', 'Hari libur dihapus');
        $this->assertSame(0, Holiday::count());
    }

    public function test_hari_libur_berpaginasi_dua_belas_per_halaman_terbaru_dulu(): void
    {
        foreach (range(1, 13) as $d) {
            Holiday::create(['date' => sprintf('2026-11-%02d', $d), 'name' => sprintf('Libur nomor %02d', $d)]);
        }
        $this->actingAs($this->admin);

        $h = $this->get(route('admin.pengaturan.index'))->assertOk()->viewData('holidays');
        $this->assertSame(13, $h->total());
        $this->assertSame(12, $h->count());

        $this->get(route('admin.pengaturan.index'))->assertSee('Libur nomor 13')->assertDontSee('Libur nomor 01');
        $this->get(route('admin.pengaturan.index', ['libur' => 2]))->assertSee('Libur nomor 01')->assertDontSee('Libur nomor 13');
    }

    public function test_validasi_hari_libur(): void
    {
        Holiday::create(['date' => '2026-10-22', 'name' => 'Sudah ada']);
        $this->actingAs($this->admin);

        $this->post(route('admin.pengaturan.libur.store'), ['date' => '2026-10-22', 'name' => 'Dobel'])->assertSessionHasErrors('date');
        $this->post(route('admin.pengaturan.libur.store'), ['date' => 'besok', 'name' => 'X'])->assertSessionHasErrors('date');
        $this->post(route('admin.pengaturan.libur.store'), ['date' => '2026-10-23', 'name' => ''])->assertSessionHasErrors('name');
        $this->assertSame(1, Holiday::count());
    }

    public function test_hari_libur_memblokir_pengajuan_dan_tampil_di_kalender(): void
    {
        $item = Item::factory()->withUnits(2)->create(['max_qty_per_loan' => 1]);
        $siswa = User::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.pengaturan.libur.store'), ['date' => '2026-10-22', 'name' => 'Libur sekolah uji']);

        $this->submitLoan($siswa, $item, '2026-10-21', '2026-10-22')->assertSessionHasErrors('end_date');
        $this->submitLoan($siswa, $item, '2026-10-22', '2026-10-22')->assertSessionHasErrors('start_date');
        $this->assertSame(0, Loan::count());

        $this->actingAs($siswa)->get(route('katalog.show', $item))->assertSee('Libur sekolah uji');

        $this->actingAs($this->admin)->delete(route('admin.pengaturan.libur.destroy', Holiday::first()));
        $this->submitLoan($siswa, $item, '2026-10-21', '2026-10-22')->assertSessionHasNoErrors();
        $this->assertSame(1, Loan::count());
    }

    public function test_hari_libur_memblokir_jadwal_pengambilan(): void
    {
        Holiday::create(['date' => '2026-10-21', 'name' => 'Libur uji']);
        $loan = Loan::factory()->between('2026-10-21', '2026-10-23')->create();

        $this->actingAs($this->admin)->post(route('admin.verifikasi.approve', $loan), [
            'pickup_date' => '2026-10-21', 'pickup_time_start' => '09:00', 'pickup_time_end' => '10:00', 'pickup_location' => 'Ruang Sarpras',
        ])->assertSessionHasErrors('pickup_date');
    }

    public function test_siswa_tidak_boleh_mengakses_pengaturan(): void
    {
        $siswa = User::factory()->create();
        $this->actingAs($siswa);

        $this->get(route('admin.pengaturan.index'))->assertForbidden();
        $this->put(route('admin.pengaturan.update'), $this->payload(['max_loan_days' => 30]))->assertForbidden();
        $this->post(route('admin.pengaturan.libur.store'), ['date' => '2026-10-22', 'name' => 'X'])->assertForbidden();
        $this->assertNull(Setting::get('max_loan_days'));
        $this->assertSame(0, Holiday::count());
    }
}
