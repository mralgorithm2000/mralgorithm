<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTypeRequest extends FormRequest
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
        $typeId = $this->route('type')->getKey();

        return [
            'title' => ['required', 'string', 'max:255'],
            'items' => ['sometimes', 'array'],
            'items.*.type_key' => [
                'required',
                'string',
                'max:255',
                'distinct',
                Rule::unique('type_items', 'type_key')
                    ->where(fn (Builder $query): Builder => $query->where('type_id', '!=', $typeId)),
            ],
            'items.*.type_name' => ['required', 'string', 'max:255'],
            'items.*.type' => ['required', 'string', Rule::in(['text', 'dropdown', 'multiple_choice'])],
            'items.*.options' => ['nullable', 'array'],
        ];
    }
}
