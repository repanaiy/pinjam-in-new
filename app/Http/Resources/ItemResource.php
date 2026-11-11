<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category->name ?? null,
            'code' => $this->code,
            'name' => $this->name,
            'summary' => $this->summary,
            'cover' => $this->coverUrl(),
            'status' => $this->status->value,
            'max_loan_days' => $this->max_loan_days,
            'max_qty_per_loan' => $this->max_qty_per_loan,
            'description' => $this->description,
            'specs' => $this->specs,
            'package_contents' => $this->package_contents,
            'gallery' => $this->galleryItems(),
        ];
    }
}
