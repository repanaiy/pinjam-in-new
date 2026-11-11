<?php

namespace App\Jobs;

use App\Enums\LoanStatus;
use App\Exceptions\IllegalTransitionException;
use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\Foundation\Bus\Dispatchable;

/** Harian 06:00 — approved yang pickup_date = hari ini -> ready_pickup. */
class PromoteReadyPickup
{
    use Dispatchable;

    public function handle(LoanService $loans): void
    {
        Loan::query()->status(LoanStatus::Approved)->whereDate('pickup_date', today())->get()
            ->each(function (Loan $loan) use ($loans) {
                try {
                    $loans->markReadyPickup($loan);
                } catch (IllegalTransitionException) {
                    // status berubah bersamaan (mis. dibatalkan siswa): lewati
                }
            });
    }
}
