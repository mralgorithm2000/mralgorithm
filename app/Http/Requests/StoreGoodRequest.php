<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGoodRequest extends FormRequest
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
            'type_id' => ['required', 'integer', Rule::exists('types', 'id')],
            'details' => ['sometimes', 'array'],
            'details.*.good_key' => ['required', 'string', 'max:255'],
            'details.*.good_name' => ['required', 'string', 'max:255'],
            'details.*.good_value' => ['required', 'string'],
        ];
    }
}
