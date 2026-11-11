<?php

namespace Tests\Feature\Admin;

use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Services\StudentImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Excel;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    private function csv(string $content, string $name = 'siswa.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $content);
    }

    private function upload(string $content, string $name = 'siswa.csv')
    {
        return $this->actingAs($this->admin)->post(route('admin.siswa.import.store'), ['file' => $this->csv($content, $name)]);
    }

    private function service(string $content): array
    {
        return app(StudentImportService::class)->import($this->csv($content), $this->admin);
    }

    public function test_import_csv_membuat_akun_siswa_aktif(): void
    {
        $this->upload("nama,nis,kelas,no_hp\nRaka Pratama,20240182,XII IPA 2,081234567101\nNadia Putri,20240175,XII IPA 1,\nBagas Aji,20240203,XI IPS 1,081234567103\n")
            ->assertOk()
            ->assertSee('Baris dibaca')
            ->assertSee('Berhasil dibuat')
            ->assertSee('Unduh kredensial (CSV)')
            ->assertSee('raka.pratama');

        $this->assertSame(3, User::siswa()->count());
        $raka = User::where('nis', '20240182')->firstOrFail();
        $this->assertSame('siswa', $raka->role->value);
        $this->assertTrue($raka->is_active);
        $this->assertSame('XII IPA 2', $raka->kelas);
        $this->assertSame('081234567101', $raka->phone);
        $this->assertNull(User::where('nis', '20240175')->value('phone'));
        $this->assertDatabaseHas('activity_logs', ['action' => 'student_import', 'actor_id' => $this->admin->id]);
    }

    public function test_password_acak_per_siswa_dan_bisa_dipakai_masuk(): void
    {
        $result = $this->service("nama,nis,kelas\nRaka Pratama,1,XII\nNadia Putri,2,XII\n");

        $this->assertCount(2, $result['created']);
        $this->assertNotSame($result['created'][0]['password'], $result['created'][1]['password']);

        foreach ($result['created'] as $row) {
            $this->assertSame(10, strlen($row['password']));
            $user = User::where('username', $row['username'])->firstOrFail();
            $this->assertTrue(Hash::check($row['password'], $user->password));
            $this->assertNotSame($row['password'], $user->password, 'tidak disimpan polos');
        }

        $this->post(route('login.store'), ['username' => $result['created'][0]['username'], 'password' => $result['created'][0]['password'], 'role' => 'siswa'])
            ->assertRedirect(route('katalog.index'));
    }

    public function test_username_dibuat_unik_dari_nama(): void
    {
        User::factory()->create(['username' => 'budi.santoso']);

        $result = $this->service("nama,nis,kelas\nBudi Santoso,1,X\nBudi Santoso,2,X\nÉmile Zola,3,X\n");

        $this->assertSame(['budi.santoso2', 'budi.santoso3', 'emile.zola'], array_column($result['created'], 'username'));
    }

    public function test_titik_koma_bom_header_indonesia_dan_baris_kosong(): void
    {
        $csv = "\xEF\xBB\xBFNama;NIS;Kelas;No. HP\r\nRaka Pratama;20240182;XII IPA 2;081234567101\r\n\r\n;;;\r\nNadia Putri;20240175;XII IPA 1;\r\n";

        $result = $this->service($csv);

        $this->assertCount(2, $result['created']);
        $this->assertSame([], $result['errors']);
        $this->assertSame('081234567101', User::where('nis', '20240182')->value('phone'));
    }

    public function test_import_xlsx_asli_dengan_nis_numerik_dan_nol_di_depan(): void
    {
        if (! interface_exists(FromArray::class)) {
            $this->markTestSkipped('Paket maatwebsite/excel belum terpasang: jalankan "composer require maatwebsite/excel" (butuh ekstensi PHP gd dan zip).');
        }

        $export = new class implements FromArray, WithHeadings
        {
            public function array(): array
            {
                return [
                    ['Raka Pratama', 20240182, 'XII IPA 2', '081234567101'],
                    ['Nadia Putri', '007', 'XII IPA 1', null],
                    [null, null, null, null],
                ];
            }

            public function headings(): array
            {
                return ['Nama', 'NIS', 'Kelas', 'No. HP'];
            }
        };

        $raw = \Maatwebsite\Excel\Facades\Excel::raw($export, Excel::XLSX);
        $file = UploadedFile::fake()->createWithContent('siswa.xlsx', $raw);

        $result = app(StudentImportService::class)->import($file, $this->admin);

        $this->assertSame([], $result['errors']);
        $this->assertSame(['Raka Pratama', 'Nadia Putri'], array_column($result['created'], 'name'));
        $this->assertSame('20240182', User::where('username', 'raka.pratama')->value('nis'), 'angka Excel menjadi teks tanpa .0');
        $this->assertSame('007', User::where('username', 'nadia.putri')->value('nis'), 'nol di depan dipertahankan');
        $this->assertSame('081234567101', User::where('username', 'raka.pratama')->value('phone'));
    }

    public function test_baris_tidak_valid_dilaporkan_dan_baris_valid_tetap_diimpor(): void
    {
        User::factory()->create(['nis' => '999']);

        $csv = "nama,nis,kelas,no_hp\n"
            ."Valid Satu,1001,X IPA 1,081234567890\n"      // baris 2: valid
            .",1002,X IPA 1,\n"                              // baris 3: nama kosong
            ."Tanpa NIS,,X IPA 1,\n"                         // baris 4: NIS kosong
            ."Tanpa Kelas,1004,,\n"                          // baris 5: kelas kosong
            ."HP Salah,1005,X IPA 1,abc\n"                   // baris 6: HP tidak valid
            ."NIS Dobel,1001,X IPA 2,\n"                     // baris 7: dobel dalam file
            ."NIS Lama,999,X IPA 2,\n"                       // baris 8: sudah terdaftar
            ."NIS Aneh,10 06!,X IPA 2,\n"                    // baris 9: format NIS
            .'Nama '.str_repeat('a', 130).",1007,X IPA 2,\n" // baris 10: nama terlalu panjang
            ."Valid Dua,1008,X IPA 3,\n";                    // baris 11: valid

        $result = $this->service($csv);

        $this->assertSame(10, $result['total']);
        $this->assertSame(['Valid Satu', 'Valid Dua'], array_column($result['created'], 'name'));
        $this->assertSame([2, 11], array_column($result['created'], 'row'));

        $errors = collect($result['errors'])->keyBy('row');
        $this->assertSame([3, 4, 5, 6, 7, 8, 9, 10], $errors->keys()->all());
        $this->assertStringContainsString('Nama kosong', implode(' ', $errors[3]['messages']));
        $this->assertStringContainsString('NIS kosong', implode(' ', $errors[4]['messages']));
        $this->assertStringContainsString('Kelas kosong', implode(' ', $errors[5]['messages']));
        $this->assertStringContainsString('No. HP tidak valid', implode(' ', $errors[6]['messages']));
        $this->assertStringContainsString('dobel dalam file (baris 2)', implode(' ', $errors[7]['messages']));
        $this->assertStringContainsString('sudah terdaftar', implode(' ', $errors[8]['messages']));
        $this->assertStringContainsString('NIS hanya boleh', implode(' ', $errors[9]['messages']));
        $this->assertStringContainsString('Nama terlalu panjang', implode(' ', $errors[10]['messages']));

        $this->assertSame(2, User::siswa()->whereIn('nis', ['1001', '1008'])->count());
        $this->assertSame(0, User::siswa()->whereIn('nis', ['1002', '1004', '1005', '1007'])->count());
    }

    public function test_halaman_hasil_menampilkan_laporan_error(): void
    {
        $this->upload("nama,nis,kelas\nValid,1,X\nTanpa Kelas,2,\n")
            ->assertOk()->assertSee('Baris yang ditolak')->assertSee('Kelas kosong.')->assertSee('Akun yang dibuat');
    }

    public function test_nis_yang_sudah_dihapus_tetap_dianggap_terdaftar(): void
    {
        User::factory()->create(['nis' => '777'])->delete();

        $result = $this->service("nama,nis,kelas\nSiswa Baru,777,X\n");

        $this->assertCount(0, $result['created']);
        $this->assertStringContainsString('sudah terdaftar', $result['errors'][0]['messages'][0]);
    }

    public function test_kolom_wajib_tidak_lengkap_ditolak_tanpa_membuat_akun(): void
    {
        $this->upload("nama,nis\nRaka,1\n")->assertSessionHas('toast.title', 'Import gagal');
        $this->assertSame(0, User::siswa()->count());
    }

    public function test_file_tanpa_data_ditolak(): void
    {
        $this->upload("nama,nis,kelas\n")->assertSessionHas('toast.message', 'File tidak berisi data siswa.');
        $this->upload('')->assertSessionHas('toast.title', 'Import gagal');
    }

    public function test_maksimal_500_baris(): void
    {
        $rows = collect(range(1, 501))->map(fn ($i) => "Siswa {$i},N{$i},X")->implode("\n");

        $this->expectException(BusinessRuleException::class);
        $this->service("nama,nis,kelas\n{$rows}\n");
    }

    public function test_tepat_500_baris_diterima(): void
    {
        $rows = collect(range(1, 500))->map(fn ($i) => "Siswa {$i},N{$i},X")->implode("\n");

        $result = $this->service("nama,nis,kelas\n{$rows}\n");

        $this->assertCount(500, $result['created']);
        $this->assertSame(500, User::siswa()->count());
    }

    public function test_format_berkas_dan_ukuran_divalidasi(): void
    {
        $this->actingAs($this->admin)->post(route('admin.siswa.import.store'), ['file' => UploadedFile::fake()->create('siswa.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('file');
        $this->actingAs($this->admin)->post(route('admin.siswa.import.store'), ['file' => UploadedFile::fake()->create('besar.csv', 3000, 'text/csv')])
            ->assertSessionHasErrors('file');
        $this->actingAs($this->admin)->post(route('admin.siswa.import.store'), [])->assertSessionHasErrors('file');

        $this->assertSame(0, User::siswa()->count());
    }

    public function test_unduh_template(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.siswa.template'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith("nama,nis,kelas,no_hp\n", $response->streamedContent());
    }

    public function test_template_yang_diunduh_bisa_diimpor_kembali(): void
    {
        $content = $this->actingAs($this->admin)->get(route('admin.siswa.template'))->streamedContent();

        $result = $this->service($content);

        $this->assertCount(2, $result['created']);
        $this->assertSame([], $result['errors']);
    }

    public function test_halaman_import_dan_hak_akses(): void
    {
        $this->actingAs($this->admin)->get(route('admin.siswa.import'))->assertOk()->assertSee('Unduh template');

        $this->actingAs(User::factory()->create())->post(route('admin.siswa.import.store'), ['file' => $this->csv("nama,nis,kelas\nA,1,X\n")])
            ->assertForbidden();
        $this->assertSame(1, User::siswa()->count(), 'hanya akun factory milik penguji');
    }
}
