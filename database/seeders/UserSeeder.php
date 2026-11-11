<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /** Password demo (dicetak di README). Ganti sebelum produksi. */
    public const ADMIN_PASSWORD = 'admin12345';

    public const SISWA_PASSWORD = 'siswa12345';

    public function run(): void
    {
        User::query()->updateOrCreate(['username' => 'maya.sari'], [
            'name' => 'Ibu Maya Sari',
            'email' => 'maya.sari@sman-nusantara.sch.id',
            'password' => self::ADMIN_PASSWORD,
            'role' => Role::Admin->value,
            'employee_code' => 'ADM-004',
            'phone' => '081234567001',
            'is_active' => true,
        ]);

        $siswa = [
            ['Raka Pratama', 'raka.pratama', '20240182', 'XII IPA 2', '081234567101'],
            ['Nadia Putri', 'nadia.putri', '20240175', 'XII IPA 1', '081234567102'],
            ['Bagas Aji', 'bagas.aji', '20240203', 'XI IPS 1', '081234567103'],
            ['Salsa Nur', 'salsa.nur', '20240219', 'XI IPA 3', '081234567104'],
            ['Rizky Akbar', 'rizky.akbar', '20240231', 'X IPA 2', '081234567105'],
            ['Alya Rahma', 'alya.rahma', '20240248', 'X IPS 2', '081234567106'],
        ];

        foreach ($siswa as [$name, $username, $nis, $kelas, $phone]) {
            User::query()->updateOrCreate(['username' => $username], [
                'name' => $name,
                'email' => $username.'@siswa.sman-nusantara.sch.id',
                'password' => self::SISWA_PASSWORD,
                'role' => Role::Siswa->value,
                'nis' => $nis,
                'kelas' => $kelas,
                'phone' => $phone,
                'is_active' => true,
            ]);
        }
    }
}
