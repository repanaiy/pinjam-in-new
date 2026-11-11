<?php

namespace Tests\Feature\Admin;

use App\Enums\LoanStatus;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SiswaManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-21 10:00:00'));
        $this->admin = User::factory()->admin()->create();
    }

    private function payload(array $o = []): array
    {
        return array_merge([
            'name' => 'Raka Pratama',
            'username' => 'Raka.Pratama',
            'email' => 'raka@example.test',
            'nis' => '20240182',
            'kelas' => 'XII IPA 2',
            'phone' => '081234567101',
            'is_active' => '1',
            'password' => 'rahasia123',
        ], $o);
    }

    private function login(string $username, string $password)
    {
        return $this->post(route('login.store'), ['username' => $username, 'password' => $password, 'role' => 'siswa']);
    }

    public function test_daftar_cari_dan_filter(): void
    {
        User::factory()->create(['name' => 'Budi Santoso', 'nis' => '111', 'kelas' => 'X IPA 1', 'username' => 'budi.s']);
        User::factory()->create(['name' => 'Citra Dewi', 'nis' => '222', 'kelas' => 'XI IPS 1', 'username' => 'citra.d', 'is_active' => false]);

        $this->actingAs($this->admin);
        $this->get(route('admin.siswa.index'))->assertOk()->assertSee('Budi Santoso')->assertSee('Citra Dewi');
        $this->get(route('admin.siswa.index', ['q' => 'Budi']))->assertSee('Budi Santoso')->assertDontSee('Citra Dewi');
        $this->get(route('admin.siswa.index', ['q' => '222']))->assertSee('Citra Dewi')->assertDontSee('Budi Santoso');
        $this->get(route('admin.siswa.index', ['kelas' => 'X IPA 1']))->assertSee('Budi Santoso')->assertDontSee('Citra Dewi');
        $this->get(route('admin.siswa.index', ['status' => 'nonaktif']))->assertSee('Citra Dewi')->assertDontSee('Budi Santoso');
        $this->get(route('admin.siswa.index', ['q' => 'tidak-ada']))->assertSee('Tidak ada siswa yang cocok');
    }

    public function test_daftar_hanya_memuat_siswa_bukan_admin(): void
    {
        User::factory()->admin()->create(['name' => 'Admin Lain']);

        $this->actingAs($this->admin)->get(route('admin.siswa.index'))->assertDontSee('Admin Lain');
    }

    public function test_tambah_siswa_dan_siswa_bisa_masuk(): void
    {
        $this->actingAs($this->admin)->post(route('admin.siswa.store'), $this->payload())
            ->assertRedirect(route('admin.siswa.index'))->assertSessionHasNoErrors();

        $s = User::where('nis', '20240182')->firstOrFail();
        $this->assertSame('siswa', $s->role->value);
        $this->assertSame('raka.pratama', $s->username, 'username dinormalkan huruf kecil');
        $this->assertTrue($s->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'student_created', 'actor_id' => $this->admin->id]);

        auth()->guard()->logout();
        $this->login('raka.pratama', 'rahasia123')->assertRedirect(route('katalog.index'));
        $this->assertAuthenticatedAs($s);
    }

    public function test_validasi_tambah_siswa(): void
    {
        User::factory()->create(['nis' => '111', 'username' => 'sudah.ada', 'email' => 'dobel@example.test']);
        $this->actingAs($this->admin);
        $url = route('admin.siswa.store');

        $this->post($url, $this->payload(['nis' => '111']))->assertSessionHasErrors('nis');
        $this->post($url, $this->payload(['username' => 'sudah.ada']))->assertSessionHasErrors('username');
        $this->post($url, $this->payload(['email' => 'dobel@example.test']))->assertSessionHasErrors('email');
        $this->post($url, $this->payload(['email' => 'bukan-email']))->assertSessionHasErrors('email');
        $this->post($url, $this->payload(['username' => 'ada spasi']))->assertSessionHasErrors('username');
        $this->post($url, $this->payload(['name' => '']))->assertSessionHasErrors('name');
        $this->post($url, $this->payload(['kelas' => '']))->assertSessionHasErrors('kelas');
        $this->post($url, $this->payload(['phone' => 'abc']))->assertSessionHasErrors('phone');
        $this->post($url, $this->payload(['password' => '']))->assertSessionHasErrors('password');
        $this->post($url, $this->payload(['password' => 'pendek']))->assertSessionHasErrors('password');

        $this->assertSame(1, User::siswa()->count());
    }

    public function test_nis_unik_juga_terhadap_siswa_yang_dihapus(): void
    {
        $gone = User::factory()->create(['nis' => '555']);
        $gone->delete();

        $this->actingAs($this->admin)->post(route('admin.siswa.store'), $this->payload(['nis' => '555']))->assertSessionHasErrors('nis');
    }

    public function test_ubah_siswa_dengan_dan_tanpa_password(): void
    {
        $s = User::factory()->create(['nis' => '300', 'username' => 'lama', 'name' => 'Nama Lama']);
        $old = $s->password;
        $this->actingAs($this->admin);

        $this->put(route('admin.siswa.update', $s->id), $this->payload(['nis' => '300', 'username' => 'lama', 'name' => 'Nama Baru', 'password' => '']))
            ->assertRedirect(route('admin.siswa.index'))->assertSessionHasNoErrors();
        $this->assertSame('Nama Baru', $s->fresh()->name);
        $this->assertSame($old, $s->fresh()->password, 'password tidak berubah bila dikosongkan');

        $this->put(route('admin.siswa.update', $s->id), $this->payload(['nis' => '300', 'username' => 'lama', 'password' => 'passwordbaru1']))->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('passwordbaru1', $s->fresh()->password));
    }

    public function test_ubah_siswa_tidak_boleh_memakai_nis_siswa_lain(): void
    {
        User::factory()->create(['nis' => '401']);
        $s = User::factory()->create(['nis' => '402', 'username' => 'dua']);

        $this->actingAs($this->admin)->put(route('admin.siswa.update', $s->id), $this->payload(['nis' => '401', 'username' => 'dua', 'password' => '']))
            ->assertSessionHasErrors('nis');
    }

    public function test_nonaktifkan_memblokir_login_dan_aktifkan_memulihkan(): void
    {
        $s = User::factory()->create(['username' => 'raka']);

        $this->actingAs($this->admin)->post(route('admin.siswa.toggle', $s->id))->assertSessionHas('toast.title', 'Akun dinonaktifkan');
        $this->assertFalse($s->fresh()->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'student_deactivated']);

        auth()->guard()->logout();
        $this->login('raka', 'password')->assertSessionHas('toast.title', 'Password Salah');
        $this->assertGuest();

        $this->actingAs($this->admin)->post(route('admin.siswa.toggle', $s->id))->assertSessionHas('toast.title', 'Akun diaktifkan');
        auth()->guard()->logout();
        $this->login('raka', 'password')->assertRedirect(route('katalog.index'));
    }

    public function test_siswa_nonaktif_yang_sudah_masuk_langsung_ditolak(): void
    {
        $s = User::factory()->create(['is_active' => false]);

        $this->actingAs($s)->get(route('katalog.index'))->assertForbidden();
    }

    public function test_reset_password_menampilkan_password_sementara_sekali(): void
    {
        $s = User::factory()->create(['username' => 'raka', 'name' => 'Raka Pratama']);

        $response = $this->actingAs($this->admin)->post(route('admin.siswa.reset', $s->id));
        $response->assertSessionHas('temp_password');
        $temp = session('temp_password');

        $this->assertSame('raka', $temp['username']);
        $this->assertSame(10, strlen($temp['password']));
        $this->assertTrue(Hash::check($temp['password'], $s->fresh()->password));
        $this->assertFalse(Hash::check('password', $s->fresh()->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'student_password_reset']);

        $this->get(route('admin.siswa.index'))->assertSee($temp['password']);
        $this->get(route('admin.siswa.index'))->assertDontSee($temp['password']); // flash: hanya sekali
    }

    public function test_hapus_siswa_soft_delete_dan_riwayat_tetap_tampil(): void
    {
        $s = User::factory()->create(['name' => 'Siswa Terhapus']);
        $item = Item::factory()->withUnits(1)->create();
        $loan = Loan::factory()->status(LoanStatus::Returned)->create(['user_id' => $s->id, 'item_id' => $item->id, 'request_code' => 'REQ-2026-8001']);

        $this->actingAs($this->admin)->delete(route('admin.siswa.destroy', $s->id))->assertRedirect(route('admin.siswa.index'));

        $this->assertSoftDeleted('users', ['id' => $s->id]);
        $this->assertDatabaseHas('loans', ['id' => $loan->id]);

        $this->actingAs($this->admin);
        $this->get(route('admin.verifikasi.show', $loan))->assertOk()->assertSee('Siswa Terhapus');
        $this->get(route('admin.laporan.index'))->assertOk()->assertSee('Siswa Terhapus');
        $this->get(route('admin.audit.index'))->assertOk();
        $this->get(route('admin.siswa.index'))->assertDontSee('Siswa Terhapus');
    }

    public function test_hapus_siswa_ditolak_bila_masih_punya_peminjaman_aktif(): void
    {
        $s = User::factory()->create();
        Loan::factory()->status(LoanStatus::Borrowed)->create(['user_id' => $s->id]);

        $this->actingAs($this->admin)->delete(route('admin.siswa.destroy', $s->id))->assertSessionHas('toast.title', 'Tidak bisa dihapus');
        $this->assertNotSoftDeleted('users', ['id' => $s->id]);
    }

    public function test_aksi_hanya_berlaku_untuk_siswa_bukan_admin(): void
    {
        $other = User::factory()->admin()->create();

        $this->actingAs($this->admin);
        $this->get(route('admin.siswa.edit', $other->id))->assertNotFound();
        $this->post(route('admin.siswa.toggle', $other->id))->assertNotFound();
        $this->post(route('admin.siswa.reset', $other->id))->assertNotFound();
        $this->delete(route('admin.siswa.destroy', $other->id))->assertNotFound();
        $this->assertTrue($other->fresh()->is_active);
    }

    public function test_siswa_tidak_boleh_mengakses_manajemen_siswa(): void
    {
        $siswa = User::factory()->create();
        $target = User::factory()->create();
        $this->actingAs($siswa);

        $this->get(route('admin.siswa.index'))->assertForbidden();
        $this->get(route('admin.siswa.create'))->assertForbidden();
        $this->post(route('admin.siswa.store'), $this->payload())->assertForbidden();
        $this->post(route('admin.siswa.toggle', $target->id))->assertForbidden();
        $this->post(route('admin.siswa.reset', $target->id))->assertForbidden();
        $this->delete(route('admin.siswa.destroy', $target->id))->assertForbidden();
        $this->get(route('admin.siswa.import'))->assertForbidden();
    }
}
