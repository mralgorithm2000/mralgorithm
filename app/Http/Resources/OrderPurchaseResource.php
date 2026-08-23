<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderPurchaseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'marketplace' => $this->marketplace,
            'marketplace_order_id' => $this->marketplace_order_id,
            'goods_id' => $this->goods_id,
            'sold_price' => $this->sold_price,
            'cost_price' => $this->cost_price,
            'marketplace_fee' => $this->marketplace_fee,
            'refunded_amount' => $this->refunded_amount,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'good' => $this->whenLoaded('good', fn () => new GoodResource($this->good)),
        ];
    }
}
