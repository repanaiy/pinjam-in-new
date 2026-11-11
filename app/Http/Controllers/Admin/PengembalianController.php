<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LoanStatus;
use App\Exceptions\IllegalTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReturnRequest;
use App\Models\ItemUnit;
use App\Models\Loan;
use App\Services\ReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengembalianController extends Controller
{
    private const ACTIVE = [LoanStatus::Borrowed, LoanStatus::Overdue];

    /** Cari peminjaman aktif (borrowed/overdue). */
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $filter = in_array($request->query('f'), ['hari-ini', 'terlambat'], true) ? $request->query('f') : 'semua';

        $base = Loan::query()->status(self::ACTIVE);

        $loans = (clone $base)
            ->with(['user', 'item', 'loanUnits.unit'])
            ->when($q !== '', fn ($b) => $b->where(function ($w) use ($q) {
                $w->where('request_code', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")->orWhere('nis', 'like', "%{$q}%")->orWhere('username', 'like', "%{$q}%"))
                    ->orWhereHas('item', fn ($i) => $i->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"))
                    ->orWhereHas('loanUnits.unit', fn ($u) => $u->where('unit_code', 'like', "%{$q}%"));
            }))
            ->when($filter === 'hari-ini', fn ($b) => $b->whereDate('end_date', today()))
            ->when($filter === 'terlambat', fn ($b) => $b->whereDate('end_date', '<', today()))
            ->orderBy('end_date')->orderBy('id')
            ->paginate(10)->withQueryString();

        return view('admin.pengembalian.index', [
            'loans' => $loans,
            'q' => $q,
            'filter' => $filter,
            'counts' => [
                'semua' => (clone $base)->count(),
                'hari-ini' => (clone $base)->whereDate('end_date', today())->count(),
                'terlambat' => (clone $base)->whereDate('end_date', '<', today())->count(),
            ],
        ]);
    }

    public function show(Loan $loan): View|RedirectResponse
    {
        if (! in_array($loan->status, self::ACTIVE, true)) {
            return redirect()->route('admin.pengembalian.index')->with('toast', [
                'type' => 'warning',
                'title' => 'Tidak ada peminjaman aktif',
                'message' => 'Pengajuan ini tidak sedang dipinjam sehingga tidak bisa diproses pengembaliannya.',
            ]);
        }

        $loan->load(['user', 'item.category', 'loanUnits.unit', 'damageReports']);
        $handover = $loan->inspections()->where('type', 'handover')->with(['media', 'inspector'])->first();

        return view('admin.pengembalian.show', [
            'loan' => $loan,
            'handover' => $handover,
            'checklist' => $loan->item->checklistItems(),
            'badge' => $loan->dueBadge(),
            'lateDays' => $loan->lateDaysAt(now()),
            'availableNow' => ItemUnit::query()->where('item_id', $loan->item_id)->lendable()->count(),
        ]);
    }

    public function store(ReturnRequest $request, Loan $loan, ReturnService $returns): RedirectResponse
    {
        try {
            $returns->complete(
                $loan,
                $request->user(),
                $request->validated('conditions'),
                $request->validated('checklist') ?? [],
                $request->validated('notes'),
                $request->file('media', []),
            );
        } catch (IllegalTransitionException) {
            return redirect()->route('admin.pengembalian.index')->with('toast', [
                'type' => 'error', 'title' => 'Status sudah berubah', 'message' => 'Pengajuan ini sudah diproses. Muat ulang halaman.',
            ]);
        }

        return redirect()->route('admin.pengembalian.index')->with('toast', [
            'type' => 'success',
            'title' => 'Transaksi selesai',
            'message' => "Pengembalian {$loan->request_code} tercatat dan siswa sudah menerima bukti pengembalian.",
        ]);
    }
}
