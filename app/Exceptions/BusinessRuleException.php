<?php

namespace App\Exceptions;

use RuntimeException;

/** Pelanggaran aturan bisnis yang pesannya aman ditampilkan ke admin (mis. hapus alat yang masih dipinjam). */
class BusinessRuleException extends RuntimeException {}
