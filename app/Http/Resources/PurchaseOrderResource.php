<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_id' => $this->purchase_id,
            'supplier_order_id' => $this->supplier_order_id,
            'status' => $this->status,
            'sold_price' => $this->sold_price,
            'cost_price' => $this->cost_price,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'order_details' => OrderDetailResource::collection($this->whenLoaded('orderDetails')),
        ];
    }
}
