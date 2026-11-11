<?php

namespace App\Exceptions;

use App\Enums\LoanStatus;
use RuntimeException;

class IllegalTransitionException extends RuntimeException
{
    public function __construct(public readonly LoanStatus $from, public readonly LoanStatus $to)
    {
        parent::__construct("Transisi status {$from->value} → {$to->value} tidak diizinkan.");
    }
}
