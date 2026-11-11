<?php

namespace App\Jobs;

use App\Enums\LoanStatus;
use App\Exceptions\IllegalTransitionException;
use App\Models\Loan;
use App\Models\Setting;
use App\Services\LoanService;
use App\Services\ServiceCalendar;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Harian 15:30 — approved/ready_pickup yang tidak diambil lewat jadwal + toleransi -> expired.
 * Batas = pickup_date + `pickup_grace_workdays` hari kerja, pada jam tutup layanan.
 */
class ExpireUnclaimed
{
    use Dispatchable;

    public function handle(LoanService $loans, ServiceCalendar $calendar): void
    {
        $grace = (int) Setting::get('pickup_grace_workdays', 1);
        $closing = (string) Setting::get('service_hour_end', '15:00');

        Loan::query()->status([LoanStatus::Approved, LoanStatus::ReadyPickup])->get()
            ->each(function (Loan $loan) use ($loans, $calendar, $grace, $closing) {
                $deadline = $calendar->addWorkdays($loan->pickup_date ?? $loan->start_date, $grace)
                    ->setTimeFromTimeString($closing);

                if (! now()->gt($deadline)) {
                    return;
                }

                try {
                    $loans->expire($loan);
                } catch (IllegalTransitionException) {
                    // status berubah bersamaan: lewati
                }
            });
    }
}
