<?php

namespace App\Models;

use App\Enums\ItemCondition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanUnit extends Model
{
    protected $fillable = ['loan_id', 'item_unit_id', 'condition_out', 'condition_in', 'returned_at'];

    protected function casts(): array
    {
        return [
            'condition_out' => ItemCondition::class,
            'condition_in' => ItemCondition::class,
            'returned_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ItemUnit::class, 'item_unit_id')->withTrashed();
    }
}
