<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteParameterOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'marketplaces' => ['required', 'array'],
            'marketplaces.*' => ['required', 'string', 'distinct'],
        ];
    }
}
