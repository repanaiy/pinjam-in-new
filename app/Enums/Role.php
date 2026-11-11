<?php

namespace App\Enums;

enum Role: string
{
    case Siswa = 'siswa';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Siswa => 'Siswa',
            self::Admin => 'Admin (Petugas Sarpras)',
        };
    }

    public function homeRoute(): string
    {
        return match ($this) {
            self::Siswa => 'katalog.index',
            self::Admin => 'admin.dashboard',
        };
    }
}
