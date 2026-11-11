<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LoanStatus;
use App\Exceptions\IllegalTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HandoverRequest;
use App\Models\ItemUnit;
use App\Models\Loan;
use App\Services\HandoverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HandoverController extends Controller
{
    public function create(Loan $loan): View|RedirectResponse
    {
        if ($loan->status !== LoanStatus::ReadyPickup) {
            return redirect()->route('admin.verifikasi.show', $loan)->with('toast', [
                'type' => 'warning',
                'title' => 'Belum bisa serah terima',
                'message' => 'Serah terima hanya untuk pengajuan berstatus Menunggu Pengambilan.',
            ]);
        }

        $loan->load(['user', 'item.category']);

        return view('admin.handover', [
            'loan' => $loan,
            'units' => ItemUnit::query()->where('item_id', $loan->item_id)->lendable()->orderBy('unit_code')->get(),
            'checklist' => $loan->item->checklistItems(),
            'cardIsImage' => in_array(strtolower(pathinfo($loan->student_card_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'], true),
        ]);
    }

    public function store(HandoverRequest $request, Loan $loan, HandoverService $handover): RedirectResponse
    {
        try {
            $handover->handover(
                $loan,
                $request->user(),
                $request->validated('unit_ids'),
                $request->validated('checklist') ?? [],
                $request->validated('notes'),
                $request->file('media', []),
            );
        } catch (IllegalTransitionException) {
            return redirect()->route('admin.verifikasi.show', $loan)->with('toast', [
                'type' => 'error', 'title' => 'Status sudah berubah', 'message' => 'Pengajuan ini sudah diproses. Muat ulang halaman.',
            ]);
        }

        return redirect()->route('admin.verifikasi.show', $loan)->with('toast', [
            'type' => 'success',
            'title' => 'Serah terima berhasil',
            'message' => 'Status menjadi Dipinjam. Sistem mulai memantau jatuh tempo.',
        ]);
    }
}
