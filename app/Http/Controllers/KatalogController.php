<?php

namespace App\Http\Controllers;

use App\Enums\LoanStatus;
use App\Enums\UnitStatus;
use App\Models\Category;
use App\Models\Item;
use App\Models\Loan;
use App\Models\Setting;
use App\Services\AvailabilityService;
use App\Services\ServiceCalendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class KatalogController extends Controller
{
    public function index(Request $request, AvailabilityService $availability): View
    {
        $today = today()->toDateString();
        $q = trim((string) $request->query('q', ''));
        $categorySlug = (string) $request->query('kategori', '');
        $sort = in_array($request->query('urut'), ['stok', 'nama', 'terbaru'], true) ? $request->query('urut') : 'stok';
        $onlyReady = $request->boolean('tersedia');

        $query = Item::query()
            ->active()
            ->with(['category', 'cover'])
            ->withCount([
                'units as total_units',
                'units as avail_units' => fn ($u) => $u->where('status', UnitStatus::Available->value),
            ])
            ->withSum(['loans as reserved_today' => fn ($l) => $l->reserving()->overlapping($today, $today)], 'quantity')
            ->search($q)
            ->when($categorySlug, fn ($b) => $b->whereHas('category', fn ($c) => $c->where('slug', $categorySlug)));

        // Stok siap hari ini sebagai ekspresi SQL (tanpa bergantung pada alias select).
        $reserving = "'".implode("','", LoanStatus::reservingValues())."'";
        $readySql = "((select count(*) from item_units iu where iu.item_id = items.id and iu.status = 'available' and iu.deleted_at is null)"
            ." - coalesce((select sum(l.quantity) from loans l where l.item_id = items.id and l.status in ($reserving)"
            .' and date(l.start_date) <= ? and date(l.end_date) >= ? and l.deleted_at is null), 0))';

        if ($onlyReady) {
            $query->whereRaw("$readySql > 0", [$today, $today]);
        }

        match ($sort) {
            'nama' => $query->orderBy('name'),
            'terbaru' => $query->orderByDesc('created_at')->orderBy('name'),
            default => $query->orderByRaw("$readySql DESC", [$today, $today])->orderBy('name'),
        };

        $items = $query->paginate(9)->withQueryString();
        $items->getCollection()->each(
            fn (Item $i) => $i->setAttribute('ready_units', max(0, (int) $i->avail_units - (int) $i->reserved_today))
        );

        $categories = Category::query()
            ->withCount(['items as items_count' => fn ($i) => $i->active()])
            ->orderBy('sort_order')->get()
            ->filter(fn ($c) => $c->items_count > 0);

        return view('siswa.katalog', [
            'items' => $items,
            'categories' => $categories,
            'totalActive' => Item::active()->count(),
            'readyToday' => $availability->readyOn($today),
            'q' => $q,
            'categorySlug' => $categorySlug,
            'sort' => $sort,
            'onlyReady' => $onlyReady,
        ]);
    }

    public function show(Request $request, Item $item, AvailabilityService $availability, ServiceCalendar $calendar): View
    {
        abort_unless($item->status->value === 'active', 404);

        $item->load(['category', 'photos']);

        $from = today();
        $to = today()->addDays(120);

        // Ajukan ulang: salin data pengajuan yang ditolak ke form.
        $source = null;
        if ($request->filled('ulang')) {
            $source = Loan::query()->whereKey($request->query('ulang'))->first();
            if ($source) {
                Gate::authorize('resubmit', $source);
                abort_unless($source->item_id === $item->id, 404);
            }
        }

        $threshold = (int) Setting::get('late_warning_threshold', 3);
        $user = $request->user();

        return view('siswa.detail', [
            'item' => $item,
            'source' => $source,
            'ready' => $availability->availableQty($item, today(), today()),
            'total' => $item->units()->count(),
            'calendarConfig' => [
                'today' => $from->toDateString(),
                'start' => old('start_date', $source ? null : ''),
                'end' => old('end_date', $source ? null : ''),
                'full' => $availability->fullDates($item, $from, $to),
                'holidays' => $calendar->holidayMap($from->toDateString(), $to->toDateString()),
                'maxDays' => $item->max_loan_days,
            ],
            'lateWarning' => $threshold > 0 && $user->late_count >= $threshold,
            'reviewSla' => (int) Setting::get('review_sla_workdays', 1),
        ]);
    }
}
