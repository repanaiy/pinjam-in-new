<?php

namespace App\Policies;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    /** Siswa hanya melihat pengajuannya sendiri; admin melihat semua. */
    public function view(User $user, Loan $loan): bool
    {
        return $user->isAdmin() || $loan->user_id === $user->id;
    }

    public function viewFile(User $user, Loan $loan): bool
    {
        return $this->view($user, $loan);
    }

    public function cancel(User $user, Loan $loan): bool
    {
        return $loan->user_id === $user->id && $loan->status->canBeCancelledByStudent();
    }

    public function resubmit(User $user, Loan $loan): bool
    {
        return $loan->user_id === $user->id && $loan->status === LoanStatus::Rejected;
    }
}
