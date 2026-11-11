<?php

namespace App\Services;

use App\Enums\UnitStatus;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Loan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Satu-satunya sumber kebenaran stok (tidak ada kolom stok).
 *   tersedia = unit 'available' − SUM(quantity) loan aktif yang rentang tanggalnya beririsan
 * Identik dengan query acuan di database_pinjam_alat.sql.
 */
class AvailabilityService
{
    public function availableQty(Item|int $item, Carbon|string $start, Carbon|string $end, ?int $exceptLoanId = null): int
    {
        $itemId = $item instanceof Item ? $item->id : $item;
        $start = Carbon::parse($start)->toDateString();
        $end = Carbon::parse($end)->toDateString();

        $units = ItemUnit::query()
            ->where('item_id', $itemId)
            ->where('status', UnitStatus::Available->value)
            ->count();

        $reserved = (int) Loan::query()
            ->where('item_id', $itemId)
            ->reserving()
            ->overlapping($start, $end)
            ->when($exceptLoanId, fn ($q) => $q->where('id', '<>', $exceptLoanId))
            ->sum('quantity');

        return max(0, $units - $reserved);
    }

    public function isAvailable(Item|int $item, int $qty, Carbon|string $start, Carbon|string $end): bool
    {
        return $qty >= 1 && $qty <= $this->availableQty($item, $start, $end);
    }

    /**
     * Tanggal yang stoknya habis (tersedia ≤ 0) pada [from, to], untuk kalender.
     *
     * @return string[]
     */
    public function fullDates(Item|int $item, Carbon|string $from, Carbon|string $to): array
    {
        $itemId = $item instanceof Item ? $item->id : $item;
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->startOfDay();

        $units = ItemUnit::query()->where('item_id', $itemId)->where('status', UnitStatus::Available->value)->count();

        $loans = Loan::query()
            ->where('item_id', $itemId)
            ->reserving()
            ->overlapping($from->toDateString(), $to->toDateString())
            ->get(['start_date', 'end_date', 'quantity']);

        $full = [];
        for ($d = $from->copy(); $d <= $to; $d->addDay()) {
            $reserved = $loans
                ->filter(fn (Loan $l) => $l->start_date->lte($d) && $l->end_date->gte($d))
                ->sum('quantity');
            if ($units - $reserved <= 0) {
                $full[] = $d->toDateString();
            }
        }

        return $full;
    }

    /** Unit siap dipinjam pada suatu hari, untuk semua alat aktif (banner katalog). */
    public function readyOn(Carbon|string $date): int
    {
        $date = Carbon::parse($date)->toDateString();

        $units = ItemUnit::query()
            ->where('status', UnitStatus::Available->value)
            ->whereHas('item', fn ($q) => $q->active())
            ->count();

        $reserved = (int) Loan::query()
            ->reserving()
            ->overlapping($date, $date)
            ->whereHas('item', fn ($q) => $q->active())
            ->sum('quantity');

        return max(0, $units - $reserved);
    }

    /**
     * Stok siap per alat aktif pada suatu hari: [item_id => jumlah]. Dua query teragregasi (tanpa N+1).
     *
     * @return Collection<int,int>
     */
    public function readyOnPerItem(Carbon|string $date): Collection
    {
        $date = Carbon::parse($date)->toDateString();

        $units = ItemUnit::query()->where('status', UnitStatus::Available->value)
            ->selectRaw('item_id, count(*) as c')->groupBy('item_id')->pluck('c', 'item_id');

        $reserved = Loan::query()->reserving()->overlapping($date, $date)
            ->selectRaw('item_id, sum(quantity) as s')->groupBy('item_id')->pluck('s', 'item_id');

        return Item::query()->active()->get(['id'])
            ->mapWithKeys(fn (Item $i) => [$i->id => max(0, (int) ($units[$i->id] ?? 0) - (int) ($reserved[$i->id] ?? 0))]);
    }
}
