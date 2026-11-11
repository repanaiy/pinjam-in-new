<?php

namespace App\Models;

use App\Enums\UnitStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemUnit extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['item_id', 'unit_code', 'status', 'notes'];

    protected function casts(): array
    {
        return ['status' => UnitStatus::class];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function loanUnits(): HasMany
    {
        return $this->hasMany(LoanUnit::class);
    }

    /** Unit yang sedang dibawa siswa (loan_units belum kembali pada loan borrowed/overdue). */
    public function scopeInUse($q)
    {
        return $q->whereHas('loanUnits', fn ($lu) => $lu->whereNull('returned_at')
            ->whereHas('loan', fn ($l) => $l->whereIn('status', ['borrowed', 'overdue'])));
    }

    /** Unit available yang tidak sedang dipinjam: kandidat untuk serah terima. */
    public function scopeLendable($q)
    {
        return $q->where('status', UnitStatus::Available->value)
            ->whereDoesntHave('loanUnits', fn ($lu) => $lu->whereNull('returned_at')
                ->whereHas('loan', fn ($l) => $l->whereIn('status', ['borrowed', 'overdue'])));
    }
}
