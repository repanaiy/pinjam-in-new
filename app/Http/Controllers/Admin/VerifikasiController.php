<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LoanStatus;
use App\Exceptions\IllegalTransitionException;
use App\Exceptions\ItemNotAvailableException;
use App\Exceptions\LoanLockedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveLoanRequest;
use App\Http\Requests\Admin\RejectLoanRequest;
use App\Models\Loan;
use App\Models\Setting;
use App\Services\AvailabilityService;
use App\Services\LoanService;
use App\Services\ServiceCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerifikasiController extends Controller
{
    public function __construct(private LoanService $loans, private AvailabilityService $availability) {}

    /** Antrean default: semua yang masih perlu tindakan admin. */
    private const QUEUE = [LoanStatus::Pending, LoanStatus::Reviewing, LoanStatus::Approved, LoanStatus::ReadyPickup];

    public function index(Request $request): View
    {
        return $this->render($request, null);
    }

    public function show(Request $request, Loan $loan): View
    {
        // Membuka detail pengajuan pending = mulai meninjau (lock).
        $loan = $this->loans->startReview($loan, $request->user());

        return $this->render($request, $loan);
    }

    public function check(Loan $loan): RedirectResponse
    {
        $check = $this->eligibility($loan->fresh(['user', 'item']));

        return back()->with('toast', [
            'type' => $check['ok'] ? 'success' : 'warning',
            'title' => $check['ok'] ? 'Layak disetujui' : 'Perlu diperhatikan',
            'message' => $check['ok']
                ? "Identitas sesuai dan jadwal tidak bentrok. {$check['free']} unit tersedia pada periode ini."
                : implode(' ', $check['issues']),
        ]);
    }

    public function approve(ApproveLoanRequest $request, Loan $loan): RedirectResponse
    {
        try {
            $this->loans->approve($loan, $request->user(), $request->validated());
        } catch (ItemNotAvailableException $e) {
            return back()->withInput()->with('toast', [
                'type' => 'error',
                'title' => 'Stok tidak mencukupi',
                'message' => "Hanya {$e->available} unit tersedia pada periode ini. Tolak pengajuan atau minta siswa mengubah tanggal.",
            ]);
        } catch (LoanLockedException $e) {
            return $this->locked($e);
        } catch (IllegalTransitionException) {
            return $this->changed();
        }

        return redirect()->route('admin.verifikasi.show', $loan)->with('toast', [
            'type' => 'success', 'title' => 'Pengajuan disetujui', 'message' => 'Siswa sudah diberi tahu jadwal pengambilan.',
        ]);
    }

    public function reject(RejectLoanRequest $request, Loan $loan): RedirectResponse
    {
        try {
            $this->loans->reject($loan, $request->user(), $request->validated('reject_reason'));
        } catch (LoanLockedException $e) {
            return $this->locked($e);
        } catch (IllegalTransitionException) {
            return $this->changed();
        }

        return redirect()->route('admin.verifikasi.show', $loan)->with('toast', [
            'type' => 'success', 'title' => 'Pengajuan ditolak', 'message' => 'Reservasi dilepas dan siswa diberi tahu alasannya.',
        ]);
    }

    public function takeover(Request $request, Loan $loan): RedirectResponse
    {
        $this->loans->takeover($loan, $request->user());

        return redirect()->route('admin.verifikasi.show', $loan)->with('toast', [
            'type' => 'success', 'title' => 'Tinjauan diambil alih', 'message' => 'Sekarang kamu yang memegang pengajuan ini.',
        ]);
    }

    public function ready(Request $request, Loan $loan): RedirectResponse
    {
        try {
            $this->loans->markReadyPickup($loan, $request->user());
        } catch (IllegalTransitionException) {
            return $this->changed();
        }

        return redirect()->route('admin.verifikasi.show', $loan)->with('toast', [
            'type' => 'success', 'title' => 'Alat siap diambil', 'message' => 'Siswa sudah diberi tahu.',
        ]);
    }

    // ------------------------------------------------------------------

    private function locked(LoanLockedException $e): RedirectResponse
    {
        return back()->with('toast', ['type' => 'warning', 'title' => 'Sedang ditinjau admin lain', 'message' => $e->getMessage()]);
    }

    private function changed(): RedirectResponse
    {
        return back()->with('toast', [
            'type' => 'error', 'title' => 'Status sudah berubah', 'message' => 'Pengajuan ini sudah diproses. Muat ulang halaman.',
        ]);
    }

    /** Cek otomatis: identitas siswa + jadwal tidak bentrok (loan ini dikecualikan dari hitungan). */
    private function eligibility(Loan $loan): array
    {
        $free = $this->availability->availableQty($loan->item_id, $loan->start_date, $loan->end_date, $loan->id);
        $issues = [];

        if (! $loan->user->is_active) {
            $issues[] = 'Akun siswa nonaktif.';
        }
        if (! $loan->user->nis || ! $loan->user->kelas) {
            $issues[] = 'Data NIS/kelas siswa belum lengkap.';
        }
        if (! $loan->student_card_path) {
            $issues[] = 'Kartu pelajar belum diunggah.';
        }
        if ($free < $loan->quantity) {
            $issues[] = "Stok tidak cukup: hanya {$free} unit tersedia, diminta {$loan->quantity}.";
        }

        return ['ok' => $issues === [], 'issues' => $issues, 'free' => $free];
    }

    private function render(Request $request, ?Loan $selected): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'antrean');
        $sort = $request->query('urut') === 'terbaru' ? 'terbaru' : 'terlama';
        $from = $request->query('dari');
        $to = $request->query('sampai');

        $query = Loan::query()->with(['user', 'item'])
            ->when($status === 'antrean' || LoanStatus::tryFrom($status) === null,
                fn ($b) => $b->status(self::QUEUE),
                fn ($b) => $b->where('status', $status))
            ->when($q !== '', fn ($b) => $b->where(function ($w) use ($q) {
                $w->where('request_code', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")->orWhere('nis', 'like', "%{$q}%")->orWhere('username', 'like', "%{$q}%"))
                    ->orWhereHas('item', fn ($i) => $i->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"));
            }))
            ->when($from, fn ($b) => $b->whereDate('created_at', '>=', $from))
            ->when($to, fn ($b) => $b->whereDate('created_at', '<=', $to));

        $sort === 'terbaru' ? $query->latest() : $query->oldest();

        $detail = null;
        if ($selected) {
            $selected->load(['user', 'item.category', 'reviewer', 'activityLogs.actor', 'loanUnits.unit']);
            $detail = [
                'check' => $this->eligibility($selected),
                'locked' => $selected->status === LoanStatus::Reviewing && $selected->reviewed_by !== $request->user()->id,
                'canDecide' => in_array($selected->status, [LoanStatus::Pending, LoanStatus::Reviewing], true),
                'cardIsImage' => in_array(strtolower(pathinfo($selected->student_card_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'], true),
                'hourStart' => Setting::get('service_hour_start', '07:00'),
                'hourEnd' => Setting::get('service_hour_end', '15:00'),
                'defaultPickup' => $this->defaultPickupDate($selected),
            ];
        }

        return view('admin.verifikasi.index', [
            'loans' => $query->paginate(10)->withQueryString(),
            'selected' => $selected,
            'detail' => $detail,
            'q' => $q,
            'status' => $status,
            'sort' => $sort,
            'from' => $from,
            'to' => $to,
            'statuses' => LoanStatus::cases(),
        ]);
    }

    /** Usulan tanggal pengambilan: hari layanan berikutnya, tidak melewati tanggal kembali. */
    private function defaultPickupDate(Loan $loan): string
    {
        $cal = app(ServiceCalendar::class);
        $d = max($loan->start_date->copy(), today());
        while (! $cal->isWorkday($d) && $d->lt($loan->end_date)) {
            $d->addDay();
        }

        return $d->toDateString();
    }
}
