<?php

namespace App\Enums;

enum ItemCondition: string
{
    case Baik = 'baik';
    case Rusak = 'rusak';
    case Hilang = 'hilang';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Status loan hasil pengembalian. */
    public function loanStatus(): LoanStatus
    {
        return match ($this) {
            self::Baik => LoanStatus::Returned,
            self::Rusak => LoanStatus::ReturnedDamaged,
            self::Hilang => LoanStatus::ReturnedLost,
        };
    }

    /** Status unit fisik hasil pengembalian. */
    public function unitStatus(): UnitStatus
    {
        return match ($this) {
            self::Baik => UnitStatus::Available,
            self::Rusak => UnitStatus::Maintenance,
            self::Hilang => UnitStatus::Lost,
        };
    }
}
