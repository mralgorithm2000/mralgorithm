<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderShowResource extends OrderResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'purchase' => $this->whenLoaded(
                'purchase',
                fn () => $this->purchase === null ? null : new OrderPurchaseResource($this->purchase),
            ),
        ];
    }
}
