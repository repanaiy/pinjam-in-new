<?php

namespace App\Enums;

enum ItemStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return $this === self::Active ? 'Aktif' : 'Nonaktif';
    }
}
