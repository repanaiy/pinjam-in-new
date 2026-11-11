<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Data awal untuk PRODUKSI: pengaturan, kategori, hari libur, dan satu akun admin.
 * Tanpa siswa/alat/foto/pengajuan demo. Jalankan:  php artisan db:seed --class=ProductionSeeder
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = config('pinjam.initial_admin');
        $password = (string) ($admin['password'] ?? '');

        if (strlen($password) < 12) {
            throw new RuntimeException('Isi INITIAL_ADMIN_PASSWORD di .env (minimal 12 karakter) sebelum menjalankan ProductionSeeder.');
        }

        $this->call([SettingsSeeder::class, HolidaySeeder::class, CategorySeeder::class]);

        User::query()->updateOrCreate(['username' => strtolower((string) $admin['username'])], [
            'name' => $admin['name'],
            'email' => $admin['email'] ?: null,
            'password' => $password,
            'role' => Role::Admin->value,
            'employee_code' => 'ADM-001',
            'is_active' => true,
        ]);
    }
}
