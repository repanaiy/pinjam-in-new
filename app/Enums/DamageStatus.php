<?php

namespace App\Enums;

enum DamageStatus: string
{
    case Open = 'open';
    case InRepair = 'in_repair';
    case Resolved = 'resolved';
    case WrittenOff = 'written_off';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Terbuka',
            self::InRepair => 'Dalam Perbaikan',
            self::Resolved => 'Selesai',
            self::WrittenOff => 'Dihapusbukukan',
        };
    }
}
