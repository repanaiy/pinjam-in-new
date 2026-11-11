<?php

namespace App\Http\Controllers\Siswa;

use App\Enums\LoanStatus;
use App\Exceptions\IllegalTransitionException;
use App\Exceptions\ItemNotAvailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Siswa\StoreLoanRequest;
use App\Models\Item;
use App\Models\Loan;
use App\Services\LoanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function __construct(private LoanService $loans) {}

    /** Pengajuan Saya: yang masih berjalan + yang ditolak dan belum diajukan ulang. */
    public function index(Request $request): View
    {
        $active = [
            LoanStatus::Pending, LoanStatus::Reviewing, LoanStatus::Approved,
            LoanStatus::ReadyPickup, LoanStatus::Borrowed, LoanStatus::Overdue,
        ];

        $loans = Loan::query()
            ->where('user_id', $request->user()->id)
            ->where(function ($q) use ($active) {
                $q->status($active)
                    ->orWhere(fn ($r) => $r->status(LoanStatus::Rejected)->whereDoesntHave('resubmissions'));
            })
            ->with(['item.category', 'item.cover'])
            ->latest()
            ->paginate(10);

        return view('siswa.pengajuan.index', ['loans' => $loans]);
    }

    public function store(StoreLoanRequest $request, Item $item): RedirectResponse
    {
        abort_unless($item->status->value === 'active', 404);

        try {
            $loan = $this->loans->submit(
                $request->user(),
                $item,
                $request->safe()->only(['quantity', 'start_date', 'end_date', 'purpose']),
                $request->file('student_card'),
                $request->file('attachment'),
                $request->resubmitSource(),
            );
        } catch (ItemNotAvailableException $e) {
            // Tidak disimpan; kembali ke pemilihan tanggal.
            return back()->withInput()->with('unavailable', $e->available);
        }

        return redirect()->route('pengajuan.show', $loan)->with('toast', [
            'type' => 'success',
            'title' => 'Pengajuan terkirim',
            'message' => "Pengajuan {$loan->request_code} menunggu verifikasi admin.",
        ]);
    }

    public function show(Loan $loan): View
    {
        Gate::authorize('view', $loan);

        $loan->load(['item.category', 'item.cover', 'reviewer', 'activityLogs.actor', 'resubmissions', 'inspections.inspector', 'inspections.media']);

        return view('siswa.pengajuan.show', [
            'loan' => $loan,
            'steps' => $loan->trackerSteps(),
        ]);
    }

    public function cancel(Request $request, Loan $loan): RedirectResponse
    {
        Gate::authorize('cancel', $loan);

        $data = $request->validate(['cancel_reason' => ['nullable', 'string', 'max:255']]);

        try {
            $this->loans->cancel($loan, $request->user(), $data['cancel_reason'] ?? null);
        } catch (IllegalTransitionException) {
            // Status berubah di antara render dan submit (mis. sudah diserahterimakan).
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Tidak bisa dibatalkan',
                'message' => 'Status pengajuan sudah berubah. Muat ulang halaman.',
            ]);
        }

        return redirect()->route('pengajuan.show', $loan)->with('toast', [
            'type' => 'success',
            'title' => 'Pengajuan dibatalkan',
            'message' => 'Reservasi alat sudah dilepas.',
        ]);
    }

    public function extend(Request $request, Loan $loan): RedirectResponse
    {
        Gate::authorize('view', $loan);

        $data = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:3'],
        ]);

        try {
            $this->loans->extend($loan, $data['days'], $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Perpanjangan gagal',
                'message' => $e->getMessage(),
            ]);
        } catch (IllegalTransitionException) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Status tidak valid',
                'message' => 'Peminjaman hanya bisa diperpanjang saat status Dipinjam atau Terlambat.',
            ]);
        }

        return redirect()->route('pengajuan.show', $loan)->with('toast', [
            'type' => 'success',
            'title' => 'Perpanjangan berhasil',
            'message' => "Peminjaman diperpanjang {$data['days']} hari.",
        ]);
    }

    /** Bukti peminjaman (PDF): tersedia setelah pengajuan disetujui. */
    public function bukti(Loan $loan)
    {
        Gate::authorize('view', $loan);

        abort_unless(in_array($loan->status, [
            LoanStatus::Approved, LoanStatus::ReadyPickup, LoanStatus::Borrowed, LoanStatus::Overdue,
            LoanStatus::Returned, LoanStatus::ReturnedDamaged, LoanStatus::ReturnedLost,
        ], true), 404);

        $loan->load(['user', 'item.category', 'loanUnits.unit', 'reviewer']);

        return Pdf::loadView('pdf.bukti', ['loan' => $loan, 'generatedAt' => now()])
            ->setPaper('a4')
            ->download("bukti-peminjaman-{$loan->request_code}.pdf");
    }
}
