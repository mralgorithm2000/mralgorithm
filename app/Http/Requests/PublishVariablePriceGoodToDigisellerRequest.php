<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class PublishVariablePriceGoodToDigisellerRequest extends PublishFixedPriceGoodToDigisellerRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'add_info' => ['required', 'string'],
            'price' => ['required', 'numeric', 'gt:0'],
            'plati_category_id' => ['required', 'integer', 'min:1'],
            'unit_quantity' => [
                'required',
                'integer',
                Rule::in([1, 10, 100, 1000, 10000, 100000, 1000000, 10000000, 100000000, 1000000000]),
            ],
            'unit_name' => ['required', 'string', 'max:255'],
        ];
    }
}
