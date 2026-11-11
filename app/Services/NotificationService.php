<?php

namespace App\Services;

use App\Enums\ItemCondition;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\LoanNotification;
use Illuminate\Support\Collection;

class NotificationService
{
    public function toUser(User $user, string $kind, string $title, string $message, ?Loan $loan = null): void
    {
        $user->notify(new LoanNotification([
            'kind' => $kind,
            'title' => $title,
            'message' => $message,
            'loan_id' => $loan?->id,
        ]));
    }

    public function toAdmins(string $kind, string $title, string $message, ?Loan $loan = null): void
    {
        User::query()->admins()->active()->get()
            ->each(fn (User $admin) => $this->toUser($admin, $kind, $title, $message, $loan));
    }

    private function summary(Loan $loan): string
    {
        $loan->loadMissing('item');

        return "{$loan->request_code} — {$loan->item->name} ×{$loan->quantity} ({$loan->periodLabel()})";
    }

    public function submitted(Loan $loan): void
    {
        $this->toUser($loan->user, 'submitted', 'Pengajuan diterima',
            'Pengajuan '.$this->summary($loan).' menunggu verifikasi admin.', $loan);

        $this->toAdmins('new_submission', 'Pengajuan baru',
            "{$loan->user->name} mengajukan ".$this->summary($loan).'.', $loan);
    }

    public function approved(Loan $loan): void
    {
        $when = $loan->pickup_date?->translatedFormat('l, j M Y');
        $time = substr((string) $loan->pickup_time_start, 0, 5).'–'.substr((string) $loan->pickup_time_end, 0, 5);

        $this->toUser($loan->user, 'approved', 'Pengajuan disetujui',
            "Pengajuan {$loan->request_code} disetujui. Ambil alat {$when}, {$time} WIB di {$loan->pickup_location}. Bawa kartu pelajar asli.", $loan);
    }

    public function rejected(Loan $loan): void
    {
        $this->toUser($loan->user, 'rejected', 'Pengajuan ditolak',
            "Pengajuan {$loan->request_code} ditolak. Alasan: {$loan->reject_reason}", $loan);
    }

    public function readyPickup(Loan $loan): void
    {
        $this->toUser($loan->user, 'ready_pickup', 'Alat siap diambil',
            "Alat untuk pengajuan {$loan->request_code} sudah disiapkan. Ambil di {$loan->pickup_location} dan bawa kartu pelajar asli.", $loan);
    }

    public function handedOver(Loan $loan): void
    {
        $this->toUser($loan->user, 'handover', 'Serah terima berhasil',
            "Alat {$loan->item->name} ×{$loan->quantity} sudah kamu terima. Kembalikan paling lambat {$loan->end_date->translatedFormat('j M Y')}.", $loan);
    }

    public function cancelled(Loan $loan, User $by): void
    {
        $this->toAdmins('cancelled', 'Pengajuan dibatalkan',
            "{$by->name} membatalkan pengajuan ".$this->summary($loan).'.', $loan);
    }

    public function dueReminder(Loan $loan, bool $dueToday): void
    {
        $when = $dueToday ? 'hari ini' : 'besok';

        $this->toUser($loan->user, 'due_reminder', 'Pengingat jatuh tempo',
            "Batas pengembalian {$loan->item->name} ×{$loan->quantity} adalah {$when} ({$loan->end_date->translatedFormat('j M Y')}). Kembalikan ke petugas sarpras tepat waktu.", $loan);
    }

    public function overdue(Loan $loan): void
    {
        $this->toUser($loan->user, 'overdue', 'Peminjaman terlambat',
            "Batas pengembalian {$loan->item->name} ({$loan->request_code}) sudah lewat. Segera kembalikan alat ke petugas sarpras.", $loan);

        $this->toAdmins('overdue', 'Peminjaman terlambat',
            "{$loan->user->name} belum mengembalikan ".$this->summary($loan).'.', $loan);
    }

    public function expired(Loan $loan): void
    {
        $this->toUser($loan->user, 'expired', 'Pengajuan kedaluwarsa',
            "Alat untuk pengajuan {$loan->request_code} tidak diambil sampai batas toleransi sehingga pengajuan kedaluwarsa. Ajukan lagi bila masih perlu.", $loan);

        $this->toAdmins('expired', 'Pengajuan kedaluwarsa',
            "{$loan->user->name} tidak mengambil alat untuk ".$this->summary($loan).'.', $loan);
    }

    /** Bukti pengembalian ke siswa; isi menyesuaikan kondisi akhir. */
    public function returned(Loan $loan, ItemCondition $worst): void
    {
        $when = $loan->returned_at->translatedFormat('j M Y, H.i');
        $late = $loan->is_late ? " Terlambat {$loan->late_days} hari." : ' Tepat waktu.';

        [$kind, $title, $message] = match ($worst) {
            ItemCondition::Baik => ['returned', 'Pengembalian berhasil',
                "{$loan->item->name} ×{$loan->quantity} diterima pada {$when} dalam kondisi baik.{$late} Transaksi {$loan->request_code} selesai."],
            ItemCondition::Rusak => ['returned_damaged', 'Alat dikembalikan rusak',
                "{$loan->item->name} diterima pada {$when} dalam kondisi rusak.{$late} Petugas sarpras akan menindaklanjuti. Transaksi {$loan->request_code} selesai."],
            ItemCondition::Hilang => ['returned_lost', 'Alat dinyatakan hilang',
                "{$loan->item->name} dicatat hilang pada {$when}.{$late} Petugas sarpras akan menghubungimu. Transaksi {$loan->request_code} selesai."],
        };

        $this->toUser($loan->user, $kind, $title, $message, $loan);
    }

    /** Ringkasan satu notifikasi untuk semua pengajuan pending yang melewati SLA peninjauan. */
    public function pendingSla(Collection $loans): void
    {
        $first = $loans->first();
        $n = $loans->count();

        $this->toAdmins('sla_pending', 'Pengajuan menunggu verifikasi',
            "{$n} pengajuan melewati batas peninjauan, termasuk {$first->request_code} ({$first->user->name}). Segera tinjau.", $n === 1 ? $first : null);
    }
}
