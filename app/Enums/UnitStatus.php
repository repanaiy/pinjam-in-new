<?php

namespace App\Enums;

enum UnitStatus: string
{
    case Available = 'available';
    case Maintenance = 'maintenance';
    case Lost = 'lost';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Maintenance => 'Perlu Perbaikan',
            self::Lost => 'Hilang',
            self::Inactive => 'Nonaktif',
        };
    }
}
