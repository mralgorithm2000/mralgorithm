<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type_id' => $this->type_id,
            'type' => $this->whenLoaded('type', fn (): array => [
                'id' => $this->type->id,
                'title' => $this->type->title,
            ]),
            'details' => GoodDetailResource::collection($this->whenLoaded('details')),
            'marketplace_ids' => $this->whenLoaded(
                'marketplaceMappings',
                fn (): object => (object) $this->marketplaceMappings
                    ->pluck('marketplace_product_id', 'marketplace')
                    ->map(fn (int|string $productId): string => (string) $productId)
                    ->all(),
            ),
            'marketplace_mappings' => GoodsMarketplaceMappingResource::collection($this->whenLoaded('marketplaceMappings')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
