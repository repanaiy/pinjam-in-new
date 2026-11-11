<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DamageStatus;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KerusakanRequest;
use App\Models\DamageReport;
use App\Services\DamageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KerusakanController extends Controller
{
    public function __construct(private DamageService $damage) {}

    public function index(Request $request): View
    {
        $filter = (string) $request->query('status', 'aktif');
        $status = DamageStatus::tryFrom($filter);

        $reports = DamageReport::query()
            ->with(['unit.item', 'loan.user', 'reporter'])
            ->when($status, fn ($q) => $q->where('status', $status->value))
            ->when(! $status && $filter !== 'semua', fn ($q) => $q->whereIn('status', ['open', 'in_repair']))
            ->latest()
            ->paginate(10)->withQueryString();

        return view('admin.inventaris.kerusakan', [
            'reports' => $reports,
            'filter' => $status ? $status->value : ($filter === 'semua' ? 'semua' : 'aktif'),
            'damage' => $this->damage,
            'counts' => [
                'aktif' => DamageReport::query()->whereIn('status', ['open', 'in_repair'])->count(),
                'semua' => DamageReport::query()->count(),
            ],
        ]);
    }

    public function update(KerusakanRequest $request, DamageReport $report): RedirectResponse
    {
        try {
            $this->damage->update(
                $report,
                DamageStatus::from($request->validated('status')),
                $request->validated('resolution_note'),
                $request->validated('fine_amount'),
                $request->user()
            );
        } catch (BusinessRuleException $e) {
            return back()->with('toast', ['type' => 'error', 'title' => 'Status tidak diubah', 'message' => $e->getMessage()]);
        }

        return back()->with('toast', ['type' => 'success', 'title' => 'Laporan diperbarui', 'message' => 'Status laporan kerusakan tersimpan.']);
    }
}
