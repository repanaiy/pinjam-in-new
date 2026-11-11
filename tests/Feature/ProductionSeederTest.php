<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Loan;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class ProductionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_menolak_berjalan_tanpa_password_admin_yang_kuat(): void
    {
        config(['pinjam.initial_admin.password' => null]);
        $this->expectException(RuntimeException::class);

        $this->seed(ProductionSeeder::class);
    }

    public function test_password_pendek_ditolak_dan_tidak_membuat_apa_pun(): void
    {
        config(['pinjam.initial_admin.password' => 'pendek123']);

        try {
            $this->seed(ProductionSeeder::class);
            $this->fail('seharusnya melempar RuntimeException');
        } catch (RuntimeException) {
            $this->assertSame(0, User::count());
            $this->assertSame(0, Category::count());
        }
    }

    public function test_membuat_admin_pengaturan_kategori_tanpa_data_demo(): void
    {
        config(['pinjam.initial_admin' => ['name' => 'Bu Sarpras', 'username' => 'Sarpras', 'email' => 'sarpras@sekolah.test', 'password' => 'kata-sandi-kuat-123']]);

        $this->seed(ProductionSeeder::class);

        $admin = User::firstOrFail();
        $this->assertSame('admin', $admin->role->value);
        $this->assertSame('sarpras', $admin->username);
        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check('kata-sandi-kuat-123', $admin->password));
        $this->assertSame(1, User::count(), 'tidak ada siswa demo');
        $this->assertSame(0, Item::count());
        $this->assertSame(0, Loan::count());
        $this->assertGreaterThan(0, Category::count());
        $this->assertNotNull(Setting::get('service_days'));
    }

    public function test_dapat_dijalankan_ulang_tanpa_menggandakan_admin(): void
    {
        config(['pinjam.initial_admin.password' => 'kata-sandi-kuat-123']);

        $this->seed(ProductionSeeder::class);
        $this->seed(ProductionSeeder::class);

        $this->assertSame(1, User::count());
    }
}
