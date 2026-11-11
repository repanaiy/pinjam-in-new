<?php

namespace App\Enums;

enum LoanStatus: string
{
    case Pending = 'pending';
    case Reviewing = 'reviewing';
    case Approved = 'approved';
    case ReadyPickup = 'ready_pickup';
    case Borrowed = 'borrowed';
    case Returned = 'returned';
    case ReturnedDamaged = 'returned_damaged';
    case ReturnedLost = 'returned_lost';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Overdue = 'overdue';

    /** Status yang menahan stok (reservasi aktif). Sama dengan query acuan di SQL. */
    public static function reserving(): array
    {
        return [self::Pending, self::Reviewing, self::Approved, self::ReadyPickup, self::Borrowed, self::Overdue];
    }

    public static function reservingValues(): array
    {
        return array_map(fn (self $s) => $s->value, self::reserving());
    }

    /** Tabel transisi sesuai spesifikasi bagian 3. */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Reviewing, self::Approved, self::Rejected, self::Cancelled],
            self::Reviewing => [self::Approved, self::Rejected, self::Cancelled],
            self::Approved => [self::ReadyPickup, self::Cancelled, self::Expired],
            self::ReadyPickup => [self::Borrowed, self::Cancelled, self::Expired],
            self::Borrowed => [self::Overdue, self::Returned, self::ReturnedDamaged, self::ReturnedLost],
            self::Overdue => [self::Returned, self::ReturnedDamaged, self::ReturnedLost],
            default => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    public function holdsStock(): bool
    {
        return in_array($this, self::reserving(), true);
    }

    public function canBeCancelledByStudent(): bool
    {
        return in_array($this, [self::Pending, self::Reviewing, self::Approved, self::ReadyPickup], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Verifikasi',
            self::Reviewing => 'Sedang Ditinjau',
            self::Approved => 'Disetujui',
            self::ReadyPickup => 'Menunggu Pengambilan',
            self::Borrowed => 'Dipinjam',
            self::Returned => 'Dikembalikan',
            self::ReturnedDamaged => 'Dikembalikan (Rusak)',
            self::ReturnedLost => 'Hilang',
            self::Rejected => 'Ditolak',
            self::Cancelled => 'Dibatalkan',
            self::Expired => 'Kedaluwarsa',
            self::Overdue => 'Terlambat',
        };
    }

    /** Nama varian untuk <x-status-badge>. */
    public function tone(): string
    {
        return match ($this) {
            self::Pending, self::Reviewing => 'amber',
            self::Approved, self::ReadyPickup => 'green',
            self::Borrowed => 'navy',
            self::Returned => 'deepgreen',
            self::ReturnedDamaged, self::ReturnedLost, self::Rejected, self::Overdue => 'red',
            self::Cancelled, self::Expired => 'gray',
        };
    }

    /** Penjelasan singkat untuk banner status siswa. */
    public function description(): string
    {
        return match ($this) {
            self::Pending => 'Pengajuanmu sudah masuk dan menunggu verifikasi admin.',
            self::Reviewing => 'Admin sedang meninjau pengajuanmu.',
            self::Approved => 'Pengajuan disetujui. Alat akan disiapkan sesuai jadwal pengambilan.',
            self::ReadyPickup => 'Alat sudah disiapkan. Ambil sesuai jadwal dan bawa kartu pelajar asli.',
            self::Borrowed => 'Alat sedang kamu pinjam. Kembalikan sebelum tanggal jatuh tempo.',
            self::Returned => 'Alat sudah dikembalikan dalam kondisi baik. Terima kasih!',
            self::ReturnedDamaged => 'Alat dikembalikan dalam kondisi rusak dan sedang ditindaklanjuti petugas.',
            self::ReturnedLost => 'Alat dinyatakan hilang. Petugas sarpras akan menghubungimu.',
            self::Rejected => 'Pengajuan ditolak. Lihat alasan di bawah dan ajukan ulang bila perlu.',
            self::Cancelled => 'Pengajuan ini sudah dibatalkan.',
            self::Expired => 'Alat tidak diambil sampai batas waktu sehingga pengajuan kedaluwarsa.',
            self::Overdue => 'Peminjaman sudah melewati jatuh tempo. Segera kembalikan alat ke petugas.',
        };
    }
}
