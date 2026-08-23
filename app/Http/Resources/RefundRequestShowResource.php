<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class RefundRequestShowResource extends RefundRequestResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'purchase' => $this->whenLoaded('purchase', fn (): ?array => $this->purchase === null ? null : [
                'id' => $this->purchase->id,
                'marketplace' => $this->purchase->marketplace,
                'marketplace_order_id' => $this->purchase->marketplace_order_id,
                'goods_id' => $this->purchase->goods_id,
                'sold_price' => $this->purchase->sold_price,
                'cost_price' => $this->purchase->cost_price,
                'marketplace_fee' => $this->purchase->marketplace_fee,
                'refunded_amount' => $this->purchase->refunded_amount,
                'status' => $this->purchase->status,
                'created_at' => $this->purchase->created_at,
                'updated_at' => $this->purchase->updated_at,
                'good' => $this->purchase->relationLoaded('good') && $this->purchase->good !== null
                    ? new GoodResource($this->purchase->good)
                    : null,
            ]),
            'admin' => $this->whenLoaded(
                'admin',
                fn () => $this->admin === null ? null : new UserResource($this->admin),
            ),
        ];
    }
}
