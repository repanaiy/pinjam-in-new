<?php

namespace Tests\Feature\Siswa;

use App\Models\User;
use App\Notifications\LoanNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotifikasiProfilTest extends TestCase
{
    use RefreshDatabase;

    private function notif(User $u, string $title = 'Pengajuan diterima'): void
    {
        $u->notify(new LoanNotification(['kind' => 'submitted', 'title' => $title, 'message' => 'Pesan uji', 'loan_id' => null]));
    }

    public function test_daftar_notifikasi_dan_tandai_dibaca(): void
    {
        $siswa = User::factory()->create();
        $this->notif($siswa);
        $id = $siswa->notifications()->first()->id;

        $this->actingAs($siswa)->get(route('notifikasi.index'))->assertOk()->assertSee('Pengajuan diterima');

        $this->actingAs($siswa)->post(route('notifikasi.read', $id))->assertRedirect();
        $this->assertSame(0, $siswa->unreadNotifications()->count());
    }

    public function test_tidak_bisa_menandai_notifikasi_milik_orang_lain(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $this->notif($a);

        $this->actingAs($b)->post(route('notifikasi.read', $a->notifications()->first()->id))->assertNotFound();
        $this->assertSame(1, $a->unreadNotifications()->count());
    }

    public function test_tandai_semua_dibaca_dan_badge_di_layout(): void
    {
        $siswa = User::factory()->create();
        $this->notif($siswa, 'Satu');
        $this->notif($siswa, 'Dua');

        $this->actingAs($siswa)->get(route('katalog.index'))->assertSee('2 belum dibaca');

        $this->actingAs($siswa)->post(route('notifikasi.read-all'))->assertRedirect();
        $this->assertSame(0, $siswa->unreadNotifications()->count());
    }

    public function test_admin_juga_bisa_membuka_notifikasi(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('notifikasi.index'))->assertOk();
    }

    public function test_ubah_profil(): void
    {
        $siswa = User::factory()->create();

        $this->actingAs($siswa)->patch(route('profil.update'), ['name' => 'Nama Baru', 'phone' => '081234567890'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Nama Baru', $siswa->fresh()->name);
        $this->assertSame('081234567890', $siswa->fresh()->phone);
    }

    public function test_validasi_profil(): void
    {
        $siswa = User::factory()->create();

        $this->actingAs($siswa)->patch(route('profil.update'), ['name' => '', 'phone' => 'abc'])
            ->assertSessionHasErrors(['name', 'phone']);
    }

    public function test_unggah_avatar_ke_disk_public(): void
    {
        Storage::fake('public');
        $siswa = User::factory()->create();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        $this->actingAs($siswa)->patch(route('profil.update'), [
            'name' => $siswa->name,
            'avatar' => UploadedFile::fake()->createWithContent('a.png', $png),
        ])->assertSessionHasNoErrors();

        Storage::disk('public')->assertExists($siswa->fresh()->avatar_path);
    }

    public function test_ganti_password_butuh_password_saat_ini_yang_benar(): void
    {
        $siswa = User::factory()->create(); // password: "password"

        $this->actingAs($siswa)->put(route('profil.password'), [
            'current_password' => 'salah', 'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password', $siswa->fresh()->password));

        $this->actingAs($siswa)->put(route('profil.password'), [
            'current_password' => 'password', 'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('passwordbaru1', $siswa->fresh()->password));
    }

    public function test_konfirmasi_password_harus_cocok_dan_minimal_8(): void
    {
        $siswa = User::factory()->create();

        $this->actingAs($siswa)->put(route('profil.password'), [
            'current_password' => 'password', 'password' => 'pendek', 'password_confirmation' => 'pendek',
        ])->assertSessionHasErrors('password');

        $this->actingAs($siswa)->put(route('profil.password'), [
            'current_password' => 'password', 'password' => 'passwordbaru1', 'password_confirmation' => 'beda',
        ])->assertSessionHasErrors('password');
    }
}
