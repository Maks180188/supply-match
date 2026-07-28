<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SourcingRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'category_id' => $this->category_id,
            'created_by' => $this->created_by,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'submission_deadline' => $this->submission_deadline?->toDateString(),
            'published_at' => $this->published_at?->toISOString(),
            'keywords' => $this->whenLoaded('keywords', fn () => $this->keywords->pluck('keyword')->values()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'rejection_reason' => $this->rejection_reason,
        ];
    }
}
