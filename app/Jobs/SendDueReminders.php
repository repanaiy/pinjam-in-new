<?php

namespace App\Jobs;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\ServiceCalendar;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Harian 07:00 — pengingat H-1 (hari kerja sebelumnya) dan hari-H untuk peminjaman `borrowed`.
 * due_reminder_sent_at mencegah pengingat ganda dalam satu hari.
 */
class SendDueReminders
{
    use Dispatchable;

    public function handle(ServiceCalendar $calendar, NotificationService $notifications, AuditService $audit): void
    {
        $today = today();

        if (! $calendar->isWorkday($today)) {
            return;
        }

        Loan::query()
            ->status(LoanStatus::Borrowed)
            ->whereBetween('end_date', [$today->toDateString(), $today->copy()->addDays(5)->toDateString()])
            ->with(['user', 'item'])
            ->get()
            ->each(function (Loan $loan) use ($today, $calendar, $notifications, $audit) {
                if ($loan->due_reminder_sent_at?->isSameDay($today)) {
                    return;
                }

                $dueToday = $loan->end_date->isSameDay($today);
                $dayBefore = $calendar->previousWorkday($loan->end_date)->isSameDay($today);

                if (! $dueToday && ! $dayBefore) {
                    return;
                }

                $notifications->dueReminder($loan, $dueToday);
                $loan->forceFill(['due_reminder_sent_at' => now()])->save();
                $audit->log($loan, null, 'due_reminder', $dueToday ? 'Pengingat jatuh tempo hari ini' : 'Pengingat jatuh tempo H-1');
            });
    }
}
