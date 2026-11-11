<?php

namespace App\Models;

use App\Enums\InspectionType;
use App\Enums\ItemCondition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanInspection extends Model
{
    protected $fillable = ['loan_id', 'type', 'checklist', 'final_condition', 'notes', 'inspected_by', 'inspected_at'];

    protected function casts(): array
    {
        return [
            'type' => InspectionType::class,
            'checklist' => 'array',
            'final_condition' => ItemCondition::class,
            'inspected_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by')->withTrashed();
    }

    public function media(): HasMany
    {
        return $this->hasMany(InspectionMedia::class, 'inspection_id');
    }
}
