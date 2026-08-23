<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateParameterOptionRequest extends FormRequest
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
            'marketplaces' => ['required', 'array'],
            'marketplaces.*' => ['required', 'string', 'distinct'],
        ];
    }
}
