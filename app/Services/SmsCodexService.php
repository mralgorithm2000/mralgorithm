<?php

namespace App\Services;

use App\Models\PhoneAttempt;
use App\Models\Purchase;
use App\Models\VirtualNumber;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsCodexService
{
    /**
     * SMSCodex country ID => international dialing code.
     */
    public function getNumber(VirtualNumber $service, Purchase $purchase): PhoneAttempt
    {

        $response = Http::withToken(config('services.smscodex.api_key'))
            ->withHeader('X-API-Key', config('services.smscodex.api_key'))
            ->post(
                config('services.smscodex.base_url').'/api/v1/marketplace/fast-purchase/api',
                [
                    'service_code' => $service->service_id,
                    'country' => $service->country_id,
                    'operator' => 'any',
                    'price_limit' => (float) $service->original_price,
                    'extras' => [
                        'priority' => 'quality',
                    ],
                    'callback_url' => 'https://my.mralgorithm.ru/api/sms/webhook/smscodex',
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

        if (! isset($data['order_id'], $data['phone_number'])) {
            Log::error('SMSCodex purchase returned no completed order', ['response' => $data]);
            throw new Exception(__('sms.unable_to_purchase'), 1001);
        }

        $providerExpiresAt = $data['expires_at'] ?? null;

        if (! is_string($providerExpiresAt) || trim($providerExpiresAt) === '') {
            Log::error('SMSCodex purchase returned a missing expiration timestamp', [
                'response' => $data,
            ]);

            throw new Exception(__('sms.unable_to_purchase'), 1001);
        }

        try {
            $expiresAt = Carbon::parse($providerExpiresAt)->utc();
        } catch (\Throwable $exception) {
            Log::error('SMSCodex purchase returned an invalid expiration timestamp', [
                'expiration' => $providerExpiresAt,
                'response' => $data,
            ]);

            throw new Exception(__('sms.unable_to_purchase'), 1001, $exception);
        }

        $countryCode = $service->country_code;

        $phoneNumber = $data['phone_number'];

        // Remove the country code from the beginning of the number.
        if ($countryCode !== '' && str_starts_with($phoneNumber, "+" . $countryCode)) {
            $phoneNumber = substr($phoneNumber, strlen($countryCode));
        }

        $attempt = $purchase->phoneAttempts()->create([
            'provider_order_id' => $data['order_id'],
            'provider' => 'smscodex',
            'phone_number' => $phoneNumber,
            'country_code' => '+'.$countryCode,
            'expires_at' => $expiresAt,
        ]);

        $actualCost = $data['price'] ?? $data['cost'] ?? $data['amount'] ?? null;
        if (is_numeric($actualCost) && (float) $actualCost > 0) {
            $purchase->increment('cost_price', (float) $actualCost);
        }

        return $attempt;
    }

    public function getOrderStatus(string $order_id): array
    {
        $response = Http::withToken(config('services.smscodex.api_key'))
            ->withHeader('X-API-Key', config('services.smscodex.api_key'))
            ->get(
                config('services.smscodex.base_url').'/api/v1/marketplace/orders/'.$order_id
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
