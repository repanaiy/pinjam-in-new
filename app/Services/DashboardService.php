<?php

namespace App\Services;

use App\Enums\DamageStatus;
use App\Enums\LoanStatus;
use App\Models\DamageReport;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Loan;
use Illuminate\Support\Carbon;

/** Angka dan data grafik Dashboard Sarpras. */
class DashboardService
{
    public function __construct(private AvailabilityService $availability) {}

    public function summary(): array
    {
        $today = today();
        $days = collect(range(6, 0))->map(fn (int $i) => $today->copy()->subDays($i)); // lama -> baru

        $totalUnits = ItemUnit::query()->where('status', '<>', 'inactive')->count();
        $lendable = ItemUnit::query()->lendable()->count();

        // Satu query untuk seluruh perhitungan harian (diolah di PHP).
        $windowStart = $today->copy()->subDays(29)->startOfDay();
        $loans = Loan::query()
            ->whereNotNull('borrowed_at')
            ->where(fn ($q) => $q->whereNull('returned_at')->orWhere('returned_at', '>=', $windowStart))
            ->get(['quantity', 'borrowed_at', 'returned_at']);

        $inUseOn = fn (Carbon $d) => (int) $loans
            ->filter(fn ($l) => $l->borrowed_at->lte($d->copy()->endOfDay()) && (! $l->returned_at || $l->returned_at->gt($d->copy()->endOfDay())))
            ->sum('quantity');

        $createdPerDay = Loan::query()
            ->where('created_at', '>=', $days->first()->copy()->startOfDay())
            ->get(['created_at'])
            ->groupBy(fn ($l) => $l->created_at->toDateString());

        $sparkBorrowed = $days->map($inUseOn)->all();
        $sparkAvailable = $days->map(fn (Carbon $d) => max(0, $totalUnits - $inUseOn($d)))->all();
        $sparkPending = $days->map(fn (Carbon $d) => $createdPerDay->get($d->toDateString(), collect())->count())->all();

        return [
            'totalUnits' => $totalUnits,
            'availableUnits' => $lendable,
            'pending' => Loan::query()->status(LoanStatus::Pending)->count(),
            'borrowed' => Loan::query()->status([LoanStatus::Borrowed, LoanStatus::Overdue])->count(),
            'spark' => [
                'total' => array_fill(0, 7, $totalUnits),
                'available' => $sparkAvailable,
                'pending' => $sparkPending,
                'borrowed' => $sparkBorrowed,
            ],
            'chart' => $this->chart($days),
            'utilization' => $this->utilization($totalUnits, $loans, $today),
            'attention' => $this->attention($today),
            'queue' => Loan::query()->status([LoanStatus::Pending, LoanStatus::Reviewing])
                ->with(['user', 'item'])->oldest()->limit(8)->get(),
        ];
    }

    /** Dipinjam vs dikembalikan per hari (7 hari terakhir). */
    private function chart($days): array
    {
        $from = $days->first()->copy()->startOfDay();

        $borrowed = Loan::query()->where('borrowed_at', '>=', $from)->get(['borrowed_at'])
            ->groupBy(fn ($l) => $l->borrowed_at->toDateString());
        $returned = Loan::query()->where('returned_at', '>=', $from)->get(['returned_at'])
            ->groupBy(fn ($l) => $l->returned_at->toDateString());

        return [
            'labels' => $days->map(fn (Carbon $d) => $d->translatedFormat('j M'))->all(),
            'borrowed' => $days->map(fn (Carbon $d) => $borrowed->get($d->toDateString(), collect())->count())->all(),
            'returned' => $days->map(fn (Carbon $d) => $returned->get($d->toDateString(), collect())->count())->all(),
        ];
    }

    /** Persentase unit-hari terpakai / (unit × 30 hari). */
    private function utilization(int $totalUnits, $loans, Carbon $today): int
    {
        if ($totalUnits === 0) {
            return 0;
        }

        $start = $today->copy()->subDays(29)->startOfDay();
        $unitDays = 0;

        foreach ($loans as $l) {
            $from = $l->borrowed_at->copy()->startOfDay()->max($start);
            $to = ($l->returned_at ?? now())->copy()->startOfDay()->min($today);
            if ($to->gte($from)) {
                $unitDays += ($from->diffInDays($to) + 1) * $l->quantity;
            }
        }

        return (int) min(100, round($unitDays / ($totalUnits * 30) * 100));
    }

    private function attention(Carbon $today): array
    {
        $dueToday = Loan::query()->status(LoanStatus::Borrowed)->whereDate('end_date', $today)
            ->with(['user', 'item'])->orderBy('id')->get();

        $ready = $this->availability->readyOnPerItem($today);
        $lowStock = Item::query()->active()->whereIn('id', $ready->keys())->get()
            ->filter(fn (Item $i) => $ready[$i->id] <= $i->min_stock)
            ->map(fn (Item $i) => ['item' => $i, 'ready' => $ready[$i->id]])
            ->sortBy('ready')->values();

        return [
            'dueToday' => $dueToday,
            'overdue' => Loan::query()->status(LoanStatus::Overdue)->count(),
            'lowStock' => $lowStock,
            'openDamage' => DamageReport::query()->where('status', DamageStatus::Open->value)->count(),
        ];
    }
}
