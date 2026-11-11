<?php

namespace App\Models;

use App\Enums\DamageStatus;
use App\Enums\ItemCondition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DamageReport extends Model
{
    protected $fillable = [
        'loan_id', 'item_unit_id', 'type', 'description', 'status',
        'reported_by', 'resolved_at', 'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'type' => ItemCondition::class,
            'status' => DamageStatus::class,
            'resolved_at' => 'datetime',
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

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by')->withTrashed();
    }
}
