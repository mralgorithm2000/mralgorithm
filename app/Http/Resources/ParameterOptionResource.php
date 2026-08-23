<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParameterOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parameter_id' => $this->parameter_id,
            'option_name' => $this->option_name,
            'option_value' => $this->option_value,
            'operator' => $this->operator,
            'additional_price' => $this->additional_price,
            'original_price' => $this->original_price,
            'selling_price' => $this->selling_price,
            'supplier_name' => $this->supplier?->title,
            'supplier_product_id' => $this->supplier_product_id,
        ];
    }
}
