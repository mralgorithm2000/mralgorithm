<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SmsWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        $configuredSecret = (string) config('services.sms_webhook.secret');
        $providedSecret = (string) $this->header('X-SMS-Webhook-Secret');

        return $configuredSecret !== ''
            && $providedSecret !== ''
            && hash_equals($configuredSecret, $providedSecret);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'order_id' => [
                'required',
                'integer',
                Rule::exists('phone_attempts', 'id'),
            ],
            'sms_code' => ['required', 'string', 'max:255'],
        ];
    }
}
