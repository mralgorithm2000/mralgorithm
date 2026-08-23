<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundRequestResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'reason' => $this->reason,
            'requested_at' => $this->requested_at,
            'reviewed_at' => $this->reviewed_at,
            'completed_at' => $this->completed_at,
            'admin_note' => $this->admin_note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'purchase' => $this->whenLoaded('purchase', fn (): ?array => $this->purchase === null ? null : [
                'id' => $this->purchase->id,
                'marketplace_order_id' => $this->purchase->marketplace_order_id,
                'marketplace' => $this->purchase->marketplace,
                'good' => $this->purchase->relationLoaded('good') && $this->purchase->good !== null
                    ? ['name' => $this->purchase->good->name]
                    : null,
            ]),
        ];
    }
}
