<?php

namespace App\Exceptions;

use App\Models\User;
use RuntimeException;

/** Pengajuan sedang ditinjau admin lain (lock "reviewing"). */
class LoanLockedException extends RuntimeException
{
    public function __construct(public readonly ?User $holder)
    {
        parent::__construct('Pengajuan sedang ditinjau oleh '.($holder?->name ?? 'admin lain').'.');
    }
}
