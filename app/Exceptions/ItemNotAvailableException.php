<?php

namespace App\Exceptions;

use RuntimeException;

class ItemNotAvailableException extends RuntimeException
{
    public function __construct(public readonly int $available = 0)
    {
        parent::__construct('Alat tidak tersedia pada rentang tanggal yang dipilih.');
    }
}
