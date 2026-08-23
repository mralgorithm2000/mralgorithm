<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class PublishFixedPriceGoodToDigisellerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'add_info' => ['required', 'string'],
            'price' => ['required', 'numeric', 'gt:0'],
            'plati_category_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
