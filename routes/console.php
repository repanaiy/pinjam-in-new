<?php

use App\Jobs\ExpireUnclaimed;
use App\Jobs\MarkOverdue;
use App\Jobs\PromoteReadyPickup;
use App\Jobs\RemindAdminPending;
use App\Jobs\SendDueReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/*
| Scheduler PINJAM-IN (zona waktu aplikasi: Asia/Jakarta).
| Job dijalankan sinkron oleh schedule:run (tidak perlu queue worker).
| Cron:  * * * * * cd /path/ke/app && php artisan schedule:run >> /dev/null 2>&1
*/
Schedule::job(new PromoteReadyPickup)->dailyAt('06:00')->withoutOverlapping();
Schedule::job(new SendDueReminders)->dailyAt('07:00')->withoutOverlapping();
Schedule::job(new MarkOverdue)->dailyAt('15:30')->withoutOverlapping();
Schedule::job(new ExpireUnclaimed)->dailyAt('15:30')->withoutOverlapping();
Schedule::job(new RemindAdminPending)->hourly()->between('07:00', '15:00')->weekdays()->withoutOverlapping();

/* Backup Aplikasi Otomatis (Database & Berkas) */
Schedule::command('app:backup')->dailyAt('02:00')->withoutOverlapping();
