<?php

namespace App\Jobs;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\ServiceCalendar;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Tiap jam kerja — pending yang melewati SLA peninjauan (hari kerja) -> notifikasi ringkas ke admin.
 * pending_reminder_sent_at membatasi maksimal satu pengingat per pengajuan per hari.
 */
class RemindAdminPending
{
    use Dispatchable;

    public function handle(ServiceCalendar $calendar, NotificationService $notifications, AuditService $audit): void
    {
        if (! $calendar->isWorkday(today())) {
            return;
        }

        $sla = (int) Setting::get('review_sla_workdays', 1);

        $due = Loan::query()->status(LoanStatus::Pending)->with(['user', 'item'])->oldest()->get()
            ->filter(fn (Loan $l) => now()->gt($calendar->addWorkdays($l->created_at, $sla)->setTimeFrom($l->created_at)))
            ->reject(fn (Loan $l) => $l->pending_reminder_sent_at?->isSameDay(today()))
            ->values();

        if ($due->isEmpty()) {
            return;
        }

        $notifications->pendingSla($due);

        $due->each(function (Loan $loan) use ($audit) {
            $loan->forceFill(['pending_reminder_sent_at' => now()])->save();
            $audit->log($loan, null, 'sla_reminder', 'Pengingat admin: melewati batas peninjauan');
        });
    }
}
