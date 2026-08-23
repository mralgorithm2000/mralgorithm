<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreParameterOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'option_name' => ['required', 'string', 'max:255'],
            'option_value' => ['required', 'string', 'max:255'],
            'operator' => ['nullable', 'string', Rule::in(['+', '-', '%'])],
            'additional_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.999999'],
            'original_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999.999999'],
            'selling_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999.999999'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'supplier_product_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
