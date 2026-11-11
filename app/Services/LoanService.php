<?php

namespace App\Services;

use App\Enums\LoanStatus;
use App\Events\LoanStatusUpdated;
use App\Exceptions\IllegalTransitionException;
use App\Exceptions\ItemNotAvailableException;
use App\Exceptions\LoanLockedException;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class LoanService
{
    public function __construct(
        private AvailabilityService $availability,
        private AuditService $audit,
        private NotificationService $notifications,
    ) {}

    /**
     * Ajukan peminjaman. Cek ketersediaan dilakukan DI DALAM transaksi dengan lockForUpdate()
     * pada baris items, sehingga dua siswa tidak bisa merebut stok terakhir.
     * Tidak tersedia -> ItemNotAvailableException dan tidak ada yang disimpan.
     *
     * @param  array{quantity:int,start_date:string,end_date:string,purpose:string}  $data
     */
    public function submit(
        User $user,
        Item $item,
        array $data,
        ?UploadedFile $studentCard,
        ?UploadedFile $attachment = null,
        ?Loan $resubmitSource = null,
    ): Loan {
        $stored = [];

        try {
            return DB::transaction(function () use ($user, $item, $data, $studentCard, $attachment, $resubmitSource, &$stored) {
                Item::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();

                $available = $this->availability->availableQty($item, $data['start_date'], $data['end_date']);
                if ($data['quantity'] > $available) {
                    throw new ItemNotAvailableException($available);
                }

                // Berkas privat (disk local). Nama berkas di-hash oleh Laravel (sanitasi nama).
                if ($studentCard) {
                    $cardPath = $studentCard->store('student-cards', 'local');
                } else {
                    if (! $resubmitSource || ! $resubmitSource->student_card_path) {
                        throw new \InvalidArgumentException('Resubmit source must have a student card path');
                    }
                    $cardPath = 'student-cards/'.Str::uuid().'.'.pathinfo($resubmitSource->student_card_path, PATHINFO_EXTENSION);
                    Storage::disk('local')->copy($resubmitSource->student_card_path, $cardPath);
                }
                $stored[] = $cardPath;

                $attachmentPath = $attachment?->store('attachments', 'local');
                if ($attachmentPath) {
                    $stored[] = $attachmentPath;
                }

                $loan = Loan::create([
                    'request_code' => 'TMP-'.Str::random(20),
                    'user_id' => $user->id,
                    'item_id' => $item->id,
                    'quantity' => $data['quantity'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'purpose' => $data['purpose'],
                    'student_card_path' => $cardPath,
                    'attachment_path' => $attachmentPath,
                    'agreement_accepted' => true,
                    'status' => LoanStatus::Pending,
                    'resubmitted_from_id' => $resubmitSource?->id,
                ]);

                $loan->update(['request_code' => sprintf('REQ-%s-%04d', now()->year, 1000 + $loan->id)]);

                $this->audit->log($loan, $user, 'submitted',
                    $resubmitSource ? 'Pengajuan diajukan ulang' : 'Pengajuan dikirim',
                    ['status_to' => LoanStatus::Pending->value, 'resubmitted_from_id' => $resubmitSource?->id]);

                $this->notifications->submitted($loan->load('user', 'item'));

                return $loan;
            });
        } catch (Throwable $e) {
            // Transaksi dibatalkan: buang berkas yang sempat tersimpan.
            Storage::disk('local')->delete($stored);
            throw $e;
        }
    }

    /**
     * Satu-satunya jalur perubahan status. Menolak transisi di luar tabel (IllegalTransitionException),
     * mengunci baris loan, dan menulis audit log. Reservasi stok lepas otomatis karena
     * ketersediaan dihitung dari status — tidak ada increment/decrement manual.
     */
    public function transition(
        Loan $loan,
        LoanStatus $to,
        ?User $actor = null,
        array $attributes = [],
        ?string $action = null,
        ?string $description = null,
        array $meta = [],
    ): Loan {
        return DB::transaction(function () use ($loan, $to, $actor, $attributes, $action, $description, $meta) {
            $fresh = Loan::query()->whereKey($loan->getKey())->lockForUpdate()->firstOrFail();
            $from = $fresh->status;

            if (! $from->canTransitionTo($to)) {
                throw new IllegalTransitionException($from, $to);
            }

            $fresh->forceFill(array_merge($attributes, ['status' => $to]))->save();

            $this->audit->log($fresh, $actor, $action ?? $to->value, $description,
                array_merge(['status_from' => $from->value, 'status_to' => $to->value], $meta));

            LoanStatusUpdated::dispatch($fresh, $from->value, $to->value, $description);

            return $fresh;
        });
    }

    /** Siswa membatalkan: boleh saat pending, reviewing, approved, ready_pickup. */
    public function cancel(Loan $loan, User $actor, ?string $reason = null): Loan
    {
        $updated = $this->transition(
            $loan, LoanStatus::Cancelled, $actor,
            ['cancelled_at' => now(), 'cancel_reason' => $reason],
            'cancelled', 'Pengajuan dibatalkan oleh siswa',
        );

        $this->notifications->cancelled($updated->load('user', 'item'), $actor);

        return $updated;
    }

    // ------------------------------------------------------------------ Admin: verifikasi

    /** Admin membuka detail: pending -> reviewing (lock agar tidak diproses dua admin). */
    public function startReview(Loan $loan, User $admin): Loan
    {
        return DB::transaction(function () use ($loan, $admin) {
            $fresh = Loan::query()->whereKey($loan->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->status !== LoanStatus::Pending) {
                return $fresh;
            }

            return $this->transition($fresh, LoanStatus::Reviewing, $admin,
                ['reviewed_by' => $admin->id, 'reviewed_at' => now()],
                'reviewing', 'Admin mulai meninjau pengajuan');
        });
    }

    /** Ambil alih tinjauan yang dipegang admin lain (mis. admin tersebut tidak melanjutkan). */
    public function takeover(Loan $loan, User $admin): Loan
    {
        return DB::transaction(function () use ($loan, $admin) {
            $fresh = Loan::query()->whereKey($loan->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->status !== LoanStatus::Reviewing || $fresh->reviewed_by === $admin->id) {
                return $fresh;
            }

            $previous = $fresh->reviewed_by;
            $fresh->forceFill(['reviewed_by' => $admin->id, 'reviewed_at' => now()])->save();
            $this->audit->log($fresh, $admin, 'review_takeover', 'Tinjauan diambil alih', ['previous_reviewer_id' => $previous]);

            return $fresh;
        });
    }

    private function assertCanDecide(Loan $loan, User $admin): void
    {
        if ($loan->status === LoanStatus::Reviewing && $loan->reviewed_by !== $admin->id) {
            throw new LoanLockedException($loan->reviewer);
        }
    }

    /**
     * Setujui + jadwal pengambilan. Ketersediaan dicek ulang saat keputusan (loan ini dikecualikan
     * karena reservasinya sendiri sudah menahan stok).
     *
     * @param  array{pickup_date:string,pickup_time_start:string,pickup_time_end:string,pickup_location:string,admin_note?:?string}  $data
     */
    public function approve(Loan $loan, User $admin, array $data): Loan
    {
        return DB::transaction(function () use ($loan, $admin, $data) {
            Item::query()->whereKey($loan->item_id)->lockForUpdate()->firstOrFail();
            $fresh = Loan::query()->whereKey($loan->getKey())->lockForUpdate()->with('reviewer')->firstOrFail();

            $this->assertCanDecide($fresh, $admin);

            $available = $this->availability->availableQty($fresh->item_id, $fresh->start_date, $fresh->end_date, $fresh->id);
            if ($fresh->quantity > $available) {
                throw new ItemNotAvailableException($available);
            }

            $updated = $this->transition($fresh, LoanStatus::Approved, $admin, [
                'reviewed_by' => $fresh->reviewed_by ?? $admin->id,
                'reviewed_at' => $fresh->reviewed_at ?? now(),
                'approved_at' => now(),
                'pickup_date' => $data['pickup_date'],
                'pickup_time_start' => $data['pickup_time_start'],
                'pickup_time_end' => $data['pickup_time_end'],
                'pickup_location' => $data['pickup_location'],
                'admin_note' => $data['admin_note'] ?? null,
            ], 'approved', 'Pengajuan disetujui', ['pickup_date' => $data['pickup_date']]);

            $this->notifications->approved($updated->load('user', 'item'));

            return $updated;
        });
    }

    /** Tolak (alasan wajib). Reservasi lepas otomatis karena status tidak lagi menahan stok. */
    public function reject(Loan $loan, User $admin, string $reason): Loan
    {
        return DB::transaction(function () use ($loan, $admin, $reason) {
            $fresh = Loan::query()->whereKey($loan->getKey())->lockForUpdate()->with('reviewer')->firstOrFail();

            $this->assertCanDecide($fresh, $admin);

            $updated = $this->transition($fresh, LoanStatus::Rejected, $admin, [
                'reviewed_by' => $fresh->reviewed_by ?? $admin->id,
                'reviewed_at' => now(),
                'reject_reason' => $reason,
            ], 'rejected', 'Pengajuan ditolak', ['reason' => $reason]);

            $this->notifications->rejected($updated->load('user', 'item'));

            return $updated;
        });
    }

    /** approved -> ready_pickup (tombol admin; scheduler memakai jalur yang sama di Fase 4). */
    public function markReadyPickup(Loan $loan, ?User $actor = null): Loan
    {
        $updated = $this->transition($loan, LoanStatus::ReadyPickup, $actor, [],
            'ready_pickup', 'Alat disiapkan, menunggu pengambilan');

        $this->notifications->readyPickup($updated->load('user', 'item'));

        return $updated;
    }

    // ------------------------------------------------------------------ Otomatis (scheduler)

    /** borrowed -> overdue (lewat end_date). */
    public function markOverdue(Loan $loan): Loan
    {
        $updated = $this->transition($loan, LoanStatus::Overdue, null, [],
            'overdue', 'Lewat jatuh tempo (otomatis)');

        $this->notifications->overdue($updated->load('user', 'item'));

        return $updated;
    }

    /** approved/ready_pickup -> expired (tidak diambil). Reservasi lepas karena status tidak lagi menahan stok. */
    public function expire(Loan $loan): Loan
    {
        $updated = $this->transition($loan, LoanStatus::Expired, null, ['expired_at' => now()],
            'expired', 'Tidak diambil sampai batas toleransi (otomatis)');

        $this->notifications->expired($updated->load('user', 'item'));

        return $updated;
    }

    // ------------------------------------------------------------------ Extension

    /** Perpanjang peminjaman. Hanya untuk status borrowed/overdue. */
    public function extend(Loan $loan, int $days, ?User $actor = null): Loan
    {
        if (! in_array($loan->status, [LoanStatus::Borrowed, LoanStatus::Overdue], true)) {
            throw new \InvalidArgumentException('Perpanjangan hanya untuk status borrowed/overdue, status saat ini: '.$loan->status->value);
        }

        if ($days < 1 || $days > 3) {
            throw new \InvalidArgumentException('Extension days must be between 1 and 3.');
        }

        return DB::transaction(function () use ($loan, $days, $actor) {
            $fresh = Loan::query()->whereKey($loan->getKey())->lockForUpdate()->firstOrFail();

            $newEndDate = $fresh->end_date->addDays($days);
            $totalExtension = $fresh->extension_days + $days;

            if ($totalExtension > 3) {
                throw new \InvalidArgumentException('Total extension cannot exceed 3 days.');
            }

            if ($newEndDate->isPast()) {
                throw new \InvalidArgumentException('Extended end date must be in the future.');
            }

            $fresh->forceFill([
                'end_date' => $newEndDate,
                'extension_days' => $totalExtension,
                'extended_at' => now(),
                'status' => $fresh->status === LoanStatus::Overdue ? LoanStatus::Borrowed : $fresh->status,
            ])->save();

            $this->audit->log($fresh, $actor, 'extended', "Diperpanjang {$days} hari", [
                'extension_days' => $days,
                'total_extension_days' => $totalExtension,
                'old_end_date' => $fresh->end_date->copy()->subDays($days)->toDateString(),
                'new_end_date' => $newEndDate->toDateString(),
            ]);

            return $fresh;
        });
    }

    // ------------------------------------------------------------------ Denda pembayaran

    /** Tandai denda lunas (admin). */
    public function payFine(Loan $loan, User $actor, ?string $paymentMethod = null): Loan
    {
        return DB::transaction(function () use ($loan, $actor, $paymentMethod) {
            $fresh = Loan::query()->whereKey($loan->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->fine_paid) {
                return $fresh;
            }

            $fresh->forceFill([
                'fine_paid' => true,
                'fine_paid_at' => now(),
            ])->save();

            $this->audit->log($fresh, $actor, 'fine_paid', 'Denda dilunasi', [
                'fine_amount' => $fresh->fine_amount,
                'damage_fines' => $fresh->damageReports()->sum('fine_amount'),
                'payment_method' => $paymentMethod,
            ]);

            return $fresh;
        });
    }
}
