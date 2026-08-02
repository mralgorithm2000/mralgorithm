<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsCodexService
{
    /**
     * SMSCodex country ID => international dialing code.
     */

    public function getNumber($service, $order_id): array
    {

        $response = Http::withToken(config('services.smscodex.api_key'))
        ->withHeader('X-API-Key',config('services.smscodex.api_key'))
            ->post(
                config('services.smscodex.base_url') . '/api/v1/marketplace/fast-purchase/api',
                [
                    'service_code' => $service->service_id,
                    'country' => $service->country_id,
                    'operator' => 'any',
                    'price_limit' => (float) $service->original_price,
                    'extras' => [
                        'priority' => 'quality',
                    ],
                    "callback_url" => "https://my.mralgorithm.ru/api/sms/webhook/smscodex",
                    // 'idempotency_key' => (string) $order_id,
                ]
            );

        if (! $response->successful()) {
            Log::error('SMSCodex purchase failed', [
                'status' => $response->status(),
                'service_code' => $service->service_id,
                'country' => $service->country_id,
                'original_price' => $service->original_price,
                'body' => $response->body(),
            ]);

            throw new Exception(
                __('sms.unable_to_purchase'),
                1001
            );
        }

        $data = $response->json();

        $countryCode = $service->country_code;

        $phoneNumber = $data['phone_number'];

        // Remove the country code from the beginning of the number.
        if ($countryCode !== '' && str_starts_with($phoneNumber, $countryCode)) {
            $phoneNumber = substr($phoneNumber, strlen($countryCode));
        }

        return [
            'order_id' => $data['order_id'],
            'number' => $phoneNumber,
            'country_code' => '+' . $countryCode,
        ];
    }

    public function getOrderStatus(string $order_id): array
    {
        $response = Http::withToken(config('services.smscodex.api_key'))
            ->withHeader('X-API-Key',config('services.smscodex.api_key'))
            ->get(
                config('services.smscodex.base_url') . '/api/v1/marketplace/orders/' . $order_id
        );

        if (! $response->successful()) {
            Log::error('SMSCodex order status check failed', [
                'status' => $response->status(),
                'order_id' => $order_id,
                'body' => $response->body(),
            ]);

            throw new Exception(
                __('sms.unable_to_check_status'),
                1002
            );
        }

        return $response->json();
    }
}
