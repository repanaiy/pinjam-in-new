<?php

namespace App\Jobs;

use App\Enums\LoanStatus;
use App\Exceptions\IllegalTransitionException;
use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\Foundation\Bus\Dispatchable;

/** Harian 15:30 — borrowed dengan end_date < hari ini -> overdue + notifikasi. */
class MarkOverdue
{
    use Dispatchable;

    public function handle(LoanService $loans): void
    {
        Loan::query()->status(LoanStatus::Borrowed)->whereDate('end_date', '<', today())->get()
            ->each(function (Loan $loan) use ($loans) {
                try {
                    $loans->markOverdue($loan);
                } catch (IllegalTransitionException) {
                    // sudah dikembalikan bersamaan: lewati
                }
            });
    }
}
