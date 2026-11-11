<?php

namespace App\Models;

use App\Enums\LoanStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Loan extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'request_code', 'user_id', 'item_id', 'quantity', 'start_date', 'end_date', 'purpose',
        'student_card_path', 'attachment_path', 'agreement_accepted', 'status',
        'reviewed_by', 'reviewed_at', 'admin_note', 'reject_reason',
        'pickup_date', 'pickup_time_start', 'pickup_time_end', 'pickup_location',
        'approved_at', 'borrowed_at', 'returned_at', 'cancelled_at', 'cancel_reason', 'expired_at',
        'is_late', 'late_days', 'due_reminder_sent_at', 'pending_reminder_sent_at', 'resubmitted_from_id',
        'extension_days', 'fine_amount', 'extended_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LoanStatus::class,
            'quantity' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'pickup_date' => 'date',
            'agreement_accepted' => 'boolean',
            'is_late' => 'boolean',
            'late_days' => 'integer',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'borrowed_at' => 'datetime',
            'returned_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expired_at' => 'datetime',
            'due_reminder_sent_at' => 'datetime',
            'pending_reminder_sent_at' => 'datetime',
            'extended_at' => 'datetime',
            'extension_days' => 'integer',
            'fine_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
    }

    public function resubmittedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'resubmitted_from_id');
    }

    public function loanUnits(): HasMany
    {
        return $this->hasMany(LoanUnit::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(LoanInspection::class);
    }

    public function damageReports(): HasMany
    {
        return $this->hasMany(DamageReport::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->latest('created_at');
    }

    public function inspection(string $type): ?LoanInspection
    {
        return $this->inspections->firstWhere('type', $type);
    }

    public function scopeStatus($q, LoanStatus|array $status)
    {
        $values = collect((array) $status)->map(fn ($s) => $s instanceof LoanStatus ? $s->value : $s)->all();

        return $q->whereIn('status', $values);
    }

    /** Loan yang menahan stok. */
    public function scopeReserving($q)
    {
        return $q->whereIn('status', LoanStatus::reservingValues());
    }

    /** Loan yang rentang tanggalnya beririsan dengan [start, end]. */
    public function scopeOverlapping($q, $start, $end)
    {
        return $q->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start);
    }

    public function resubmissions(): HasMany
    {
        return $this->hasMany(self::class, 'resubmitted_from_id');
    }

    /** "21 Okt 2026 – 23 Okt 2026" */
    public function periodLabel(): string
    {
        return $this->start_date->translatedFormat('j M Y').' – '.$this->end_date->translatedFormat('j M Y');
    }

    public function durationDays(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /** 5 langkah pelacakan siswa: Pending → Approved → Menunggu Pengambilan → Dipinjam → Dikembalikan. */
    public function trackerSteps(): array
    {
        $this->loadMissing('activityLogs');

        $rank = match ($this->status) {
            LoanStatus::Approved => 1,
            LoanStatus::ReadyPickup => 2,
            LoanStatus::Borrowed, LoanStatus::Overdue => 3,
            LoanStatus::Returned, LoanStatus::ReturnedDamaged, LoanStatus::ReturnedLost => 4,
            default => 0,
        };
        $negative = in_array($this->status, [LoanStatus::Rejected, LoanStatus::Cancelled, LoanStatus::Expired], true);
        $readyAt = $this->activityLogs->firstWhere('action', 'ready_pickup')?->created_at;

        $steps = [
            ['Pending', $this->created_at],
            ['Approved', $this->approved_at],
            ['Menunggu Pengambilan', $readyAt],
            ['Dipinjam', $this->borrowed_at],
            ['Dikembalikan', $this->returned_at],
        ];

        return collect($steps)->map(function ($s, $i) use ($rank, $negative) {
            $state = $negative
                ? ($i === 0 ? 'done' : 'todo')
                : ($i < $rank ? 'done' : ($i === $rank ? ($rank === 4 ? 'done' : 'current') : 'todo'));

            return ['label' => $s[0], 'at' => $state === 'todo' ? null : $s[1], 'state' => $state];
        })->all();
    }

    /** Hari keterlambatan bila dikembalikan pada $at (perbandingan tanggal; kembali di hari jatuh tempo = tepat waktu). */
    public function lateDaysAt(Carbon|CarbonInterface $at): int
    {
        $returned = $at->copy()->startOfDay();
        $due = $this->end_date->copy()->startOfDay();

        return $returned->gt($due) ? $due->diffInDays($returned) : 0;
    }

    public function totalFineAmount(): float
    {
        return (float) $this->fine_amount + (float) $this->damageReports()->sum('fine_amount');
    }

    public function dueBadge(?CarbonInterface $at = null): array
    {
        $at ??= now();
        $late = $this->lateDaysAt($at);

        if ($late > 0) {
            return ['tone' => 'red', 'label' => "Terlambat {$late} hari"];
        }

        return $this->end_date->isSameDay($at)
            ? ['tone' => 'amber', 'label' => 'Jatuh tempo hari ini']
            : ['tone' => 'green', 'label' => 'Kembali tepat waktu'];
    }

    public function canExtend(): bool
    {
        return $this->extension_days < 3;
    }
}
