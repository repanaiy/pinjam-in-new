<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionMedia extends Model
{
    protected $table = 'inspection_media';

    protected $fillable = ['inspection_id', 'media_type', 'path', 'caption'];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(LoanInspection::class, 'inspection_id');
    }
}
