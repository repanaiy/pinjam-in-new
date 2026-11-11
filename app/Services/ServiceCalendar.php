<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\Setting;
use Illuminate\Support\Carbon;

/** Hari layanan & hari libur (pengaturan + tabel holidays). */
class ServiceCalendar
{
    /** @return int[] ISO weekday: 1 = Senin ... 7 = Minggu */
    public function serviceDays(): array
    {
        return array_values(array_filter(array_map('intval', explode(',', (string) Setting::get('service_days', '1,2,3,4,5')))));
    }

    /** @return array<string,string> ['2026-12-25' => 'Hari Raya Natal'] */
    public function holidayMap(string $from, string $to): array
    {
        return Holiday::query()
            ->whereBetween('date', [$from, $to])
            ->get()
            ->mapWithKeys(fn (Holiday $h) => [$h->date->toDateString() => $h->name])
            ->all();
    }

    public function isHoliday(Carbon|string $date): bool
    {
        return Holiday::query()->whereDate('date', Carbon::parse($date)->toDateString())->exists();
    }

    public function isWorkday(Carbon|string $date): bool
    {
        $d = Carbon::parse($date);

        return in_array($d->dayOfWeekIso, $this->serviceDays(), true) && ! $this->isHoliday($d);
    }

    public function addWorkdays(Carbon|string $date, int $days): Carbon
    {
        $d = Carbon::parse($date)->startOfDay();
        while ($days > 0) {
            $d->addDay();
            if ($this->isWorkday($d)) {
                $days--;
            }
        }

        return $d;
    }

    /** Hari kerja sebelum tanggal tertentu (untuk pengingat H-1 yang melewati akhir pekan/libur). */
    public function previousWorkday(Carbon|string $date): Carbon
    {
        $d = Carbon::parse($date)->startOfDay();
        do {
            $d->subDay();
        } while (! $this->isWorkday($d));

        return $d;
    }
}
