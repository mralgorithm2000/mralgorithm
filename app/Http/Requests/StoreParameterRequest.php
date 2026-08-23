<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreParameterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'parameter_key' => ['required', 'string', 'max:255'],
            'type' => [
                'required',
                'string',
                Rule::in([
                    'text',
                    'dropdown',
                    'radio_button',
                    'checkbox',
                    'multiline_textarea',
                ]),
            ],
            'is_main' => ['sometimes', 'boolean'],
        ];
    }
}
