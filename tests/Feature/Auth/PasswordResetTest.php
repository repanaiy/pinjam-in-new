<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private User $siswa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siswa = User::factory()->create(['username' => 'raka', 'email' => 'raka@siswa.test']);
    }

    private function requestLink(string $email = 'raka@siswa.test'): string
    {
        $token = null;
        Notification::fake();
        $this->post(route('password.email'), ['email' => $email])->assertSessionHas('status');
        Notification::assertSentTo($this->siswa, ResetPassword::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        return $token;
    }

    public function test_halaman_lupa_password(): void
    {
        $this->get(route('password.request'))->assertOk()->assertSee('Lupa password')->assertSee('Kirim tautan reset');
    }

    public function test_tautan_dikirim_hanya_untuk_email_terdaftar(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'tidak.ada@siswa.test'])->assertSessionHasErrors('email');
        Notification::assertNothingSent();

        $this->post(route('password.email'), ['email' => 'raka@siswa.test'])->assertSessionHasNoErrors();
        Notification::assertSentTo($this->siswa, ResetPassword::class);
    }

    public function test_pesan_galat_dan_email_berbahasa_indonesia(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'tidak.ada@siswa.test'])
            ->assertSessionHasErrors(['email' => 'Kami tidak dapat menemukan pengguna dengan alamat surel tersebut.']);

        $this->post(route('password.email'), ['email' => 'raka@siswa.test']);
        Notification::assertSentTo($this->siswa, ResetPassword::class, function (ResetPassword $n) {
            $mail = $n->toMail($this->siswa);

            return $mail->subject === 'Pemberitahuan Pengaturan Ulang Kata Sandi'
                && $mail->actionText === 'Atur Ulang Kata Sandi'
                && str_contains(implode(' ', $mail->introLines), 'permintaan pengaturan ulang kata sandi');
        });
    }

    public function test_reset_dengan_token_valid_lalu_masuk_dengan_password_baru(): void
    {
        $token = $this->requestLink();

        $this->get(route('password.reset', ['token' => $token, 'email' => 'raka@siswa.test']))
            ->assertOk()->assertSee('Atur ulang password')->assertSee('Simpan password baru');

        $this->post(route('password.store'), [
            'token' => $token, 'email' => 'raka@siswa.test', 'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertTrue(Hash::check('passwordbaru1', $this->siswa->fresh()->password));

        $this->post(route('login.store'), ['username' => 'raka', 'password' => 'passwordbaru1', 'role' => 'siswa'])
            ->assertRedirect(route('katalog.index'));
    }

    public function test_password_lama_tidak_berlaku_dan_token_tidak_bisa_dipakai_ulang(): void
    {
        $token = $this->requestLink();
        $data = ['token' => $token, 'email' => 'raka@siswa.test', 'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1'];

        $this->post(route('password.store'), $data)->assertRedirect(route('login'));

        $this->post(route('login.store'), ['username' => 'raka', 'password' => 'password', 'role' => 'siswa'])
            ->assertSessionHas('toast.title', 'Password Salah');

        $this->post(route('password.store'), array_merge($data, ['password' => 'lainnya12345', 'password_confirmation' => 'lainnya12345']))
            ->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('passwordbaru1', $this->siswa->fresh()->password));
    }

    public function test_token_salah_dan_password_tidak_valid_ditolak(): void
    {
        $token = $this->requestLink();
        $base = ['email' => 'raka@siswa.test', 'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1'];

        $this->post(route('password.store'), $base + ['token' => 'token-ngawur'])->assertSessionHasErrors('email');
        $this->post(route('password.store'), ['token' => $token, 'email' => 'raka@siswa.test', 'password' => 'pendek', 'password_confirmation' => 'pendek'])->assertSessionHasErrors('password');
        $this->post(route('password.store'), ['token' => $token, 'email' => 'raka@siswa.test', 'password' => 'passwordbaru1', 'password_confirmation' => 'beda'])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $this->siswa->fresh()->password), 'password tidak berubah');
    }

    public function test_login_menampilkan_pemberitahuan_setelah_reset_berhasil(): void
    {
        // Tanpa status: tidak ada pemberitahuan.
        $this->get(route('login'))->assertOk()->assertDontSee('Password berhasil diatur ulang');

        // withSession() menyimpan data biasa (bukan flash), jadi cukup satu permintaan setelahnya.
        $this->withSession(['status' => 'passwords.reset'])->get(route('login'))
            ->assertOk()->assertSee('Password berhasil diatur ulang');
    }

    public function test_pengguna_yang_sudah_masuk_tidak_bisa_membuka_halaman_reset(): void
    {
        $this->actingAs($this->siswa)->get(route('password.request'))->assertRedirect(route('katalog.index'));
    }
}
