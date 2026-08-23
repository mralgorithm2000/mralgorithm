<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_id' => $this->purchase_id,
            'supplier_order_id' => $this->supplier_order_id,
            'good_name' => $this->whenLoaded('purchase', fn () => $this->purchase?->good?->name),
            'marketplace' => $this->whenLoaded('purchase', fn () => $this->purchase?->marketplace),
            'marketplace_order_id' => $this->whenLoaded('purchase', fn () => $this->purchase?->marketplace_order_id),
            'status' => $this->status,
            'sold_price' => $this->sold_price,
            'cost_price' => $this->cost_price,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'order_details' => $this->whenLoaded(
                'orderDetails',
                fn () => OrderDetailResource::collection($this->orderDetails),
            ),
        ];
    }
}
