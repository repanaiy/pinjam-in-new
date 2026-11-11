<?php

namespace Tests\Feature\Siswa;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $pemilik;

    private Loan $loan;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::disk('local')->put('student-cards/a.png', 'isi-kartu');

        $this->pemilik = User::factory()->create();
        $this->loan = Loan::factory()->create(['user_id' => $this->pemilik->id, 'student_card_path' => 'student-cards/a.png']);
    }

    public function test_pemilik_dan_admin_boleh_mengunduh(): void
    {
        $this->actingAs($this->pemilik)->get(route('berkas.kartu', $this->loan))->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get(route('berkas.kartu', $this->loan))->assertOk();
    }

    public function test_siswa_lain_ditolak(): void
    {
        $this->actingAs(User::factory()->create())->get(route('berkas.kartu', $this->loan))->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('berkas.kartu', $this->loan))->assertRedirect(route('login'));
    }

    public function test_lampiran_kosong_404_dan_berkas_hilang_404(): void
    {
        $this->actingAs($this->pemilik)->get(route('berkas.lampiran', $this->loan))->assertNotFound();

        Storage::disk('local')->delete('student-cards/a.png');
        $this->actingAs($this->pemilik)->get(route('berkas.kartu', $this->loan))->assertNotFound();
    }

    public function test_berkas_privat_tidak_bisa_diakses_lewat_url_publik(): void
    {
        // 403 atau 404 keduanya acceptable - yang penting ditolak
        $response = $this->get('/storage/student-cards/a.png');
        $this->assertContains($response->status(), [403, 404]);
    }
}
