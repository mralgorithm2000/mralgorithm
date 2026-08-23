<?php

namespace App\Http\Requests;

use App\Enums\RefundRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRefundRequestStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(RefundRequestStatus::class)],
            'admin_note' => ['nullable', 'string'],
        ];
    }
}
