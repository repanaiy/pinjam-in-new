<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_code' => $this->request_code,
            'item_name' => $this->item->name ?? null,
            'item_cover' => $this->item->coverUrl() ?? null,
            'quantity' => $this->quantity,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'purpose' => $this->purpose,
            'reject_reason' => $this->reject_reason,
            'pickup_date' => $this->pickup_date?->toDateString(),
            'pickup_location' => $this->pickup_location,
            'borrowed_at' => $this->borrowed_at?->toIso8601String(),
            'returned_at' => $this->returned_at?->toIso8601String(),
            'is_late' => $this->is_late,
            'tracker' => $this->trackerSteps(),
        ];
    }
}
