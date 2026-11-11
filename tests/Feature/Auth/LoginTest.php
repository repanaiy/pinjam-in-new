<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function attempt(string $username, string $password, string $role)
    {
        return $this->post(route('login.store'), compact('username', 'password', 'role'));
    }

    public function test_halaman_login_tampil(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Peminjaman alat, tercatat dari awal hingga kembali.')
            ->assertSee('Butuh bantuan? Hubungi petugas sarpras.');
    }

    public function test_siswa_masuk_dan_diarahkan_ke_katalog(): void
    {
        User::factory()->create(['username' => 'raka.pratama']);

        $this->attempt('raka.pratama', 'password', 'siswa')->assertRedirect(route('katalog.index'));
        $this->assertAuthenticated();
    }

    public function test_admin_masuk_dan_diarahkan_ke_dashboard(): void
    {
        User::factory()->admin()->create(['username' => 'maya.sari']);

        $this->attempt('maya.sari', 'password', 'admin')->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_peran_yang_dipilih_tidak_cocok_ditolak_dengan_pesan_generik(): void
    {
        User::factory()->create(['username' => 'raka.pratama']);

        $this->from(route('login'))
            ->attempt('raka.pratama', 'password', 'admin')
            ->assertRedirect(route('login'))
            ->assertSessionHas('toast.title', 'Password Salah')
            ->assertSessionHas('toast.message', 'Periksa kembali password lalu coba lagi.');
        $this->assertGuest();
    }

    public function test_password_salah_memakai_pesan_yang_sama(): void
    {
        User::factory()->create(['username' => 'raka.pratama']);

        $this->attempt('raka.pratama', 'salah', 'siswa')
            ->assertSessionHas('toast.title', 'Password Salah')
            ->assertSessionHas('toast.message', 'Periksa kembali password lalu coba lagi.');
        $this->assertGuest();
    }

    public function test_username_tidak_ada_memakai_pesan_yang_sama(): void
    {
        $this->attempt('tidak.ada', 'password', 'siswa')
            ->assertSessionHas('toast.title', 'Password Salah');
        $this->assertGuest();
    }

    public function test_akun_nonaktif_tidak_bisa_masuk(): void
    {
        User::factory()->inactive()->create(['username' => 'nonaktif']);

        $this->attempt('nonaktif', 'password', 'siswa')
            ->assertSessionHas('toast.title', 'Password Salah');
        $this->assertGuest();
    }

    public function test_rate_limit_lima_percobaan_per_menit(): void
    {
        User::factory()->create(['username' => 'raka.pratama']);

        for ($i = 0; $i < 5; $i++) {
            $this->attempt('raka.pratama', 'salah', 'siswa')->assertSessionHas('toast.title', 'Password Salah');
        }

        // Percobaan ke-6 diblokir meskipun password benar.
        $this->attempt('raka.pratama', 'password', 'siswa')
            ->assertSessionHas('toast.title', 'Terlalu Banyak Percobaan');
        $this->assertGuest();
    }

    public function test_siswa_tidak_bisa_membuka_halaman_admin(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_tidak_bisa_membuka_katalog_siswa(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('katalog.index'))
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('katalog.index'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_keluar_mengakhiri_sesi(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
