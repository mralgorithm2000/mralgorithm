<?php

namespace App\Services\Suppliers;

use App\Models\PhoneAttempt;
use App\Models\Purchase;
use App\Models\VirtualNumber;
use App\Enums\PhoneAttemptStatus;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NumberlandService
{
    public function receiveSmsCode(PhoneAttempt $attempt, string $smsCode): bool
    {
        if ($attempt->status === PhoneAttemptStatus::RECEIVED->value) {
            return false;
        }

        $purchase = $attempt->purchase;

        if ($purchase->marketplace === 'plati' && $purchase->external_order_id) {
            (new DigisellerService)->markAsDelivered($purchase->external_order_id);
        } elseif ($purchase->marketplace === 'plati') {
            Log::warning('DigiSeller delivery skipped because the unique code is missing', [
                'purchase_id' => $purchase->id,
            ]);
        }

        $attempt->receiveCode($smsCode);

        return true;
    }

    /**
     * NumberLand Service IDs.
     *
     * Replace these IDs with the correct values from the getinfo API.
     */
    public function getNumber(VirtualNumber $service, Purchase $purchase, array $prices = []): PhoneAttempt
    {

        $response = Http::get(
            config('services.numberland.base_url').'/v2.php',
            [
                'apikey' => config('services.numberland.api_key'),
                'method' => 'getnum',
                'sid' => $service->service_id,
            ]
        );

        if (! $response->successful()) {

            Log::error('NumberLand purchase failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new Exception(
                __('sms.unable_to_purchase'),
                1001
            );
        }

        Log::info('NumberLand purchase status', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $data = $response->json();

        if (($data['RESULT'] ?? null) != 1) {

            Log::error('NumberLand purchase error', [
                'response' => $data,
            ]);

            throw new Exception(
                __('sms.unable_to_purchase'),
                1001
            );
        }

        $countryCode = $data['AREACODE'];

        $phoneNumber = $data['NUMBER'];

        $duration = $data['TIME'] ?? null;

        if (! is_string($duration) || ! preg_match('/^\d+:[0-5]\d:[0-5]\d$/', $duration)) {
            Log::error('NumberLand purchase returned an invalid expiration duration', [
                'expiration' => $duration,
                'response' => $data,
            ]);

            throw new Exception(__('sms.unable_to_purchase'), 1001);
        }

        [$hours, $minutes, $seconds] = array_map('intval', explode(':', $duration));
        $durationInSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;

        if ($durationInSeconds <= 0) {
            Log::error('NumberLand purchase returned a non-positive expiration duration', [
                'expiration' => $duration,
                'response' => $data,
            ]);

            throw new Exception(__('sms.unable_to_purchase'), 1001);
        }

        $expiresAt = now()->addSeconds($durationInSeconds);

        if (
            $countryCode !== '' &&
            str_starts_with($phoneNumber, $countryCode)
        ) {
            $phoneNumber = substr(
                $phoneNumber,
                strlen($countryCode)
            );
        }

        $actualCost = $data['PRICE'] ?? $data['COST'] ?? 0;

        $attempt = $purchase->phoneAttempts()->create([
            'provider_order_id' => $data['ID'],
            'provider' => 'numberland',
            'phone_number' => $phoneNumber,
            'country_code' => '+'.$countryCode,
            'expires_at' => $expiresAt,
            'sold_price' => $prices['sold_price'] ?? 0,
            'cost_price' => is_numeric($actualCost) ? max(0, (float) $actualCost) : 0,
            'marketplace_fee' => $prices['marketplace_fee'] ?? 0,
        ]);

        if (is_numeric($actualCost) && (float) $actualCost > 0) {
            $purchase->increment('cost_price', (float) $actualCost);
        }

        return $attempt;
    }

    public function getOrderStatus(string $order_id): array
    {
        $response = Http::timeout(30)
            ->connectTimeout(30)
            ->get(config('services.numberland.base_url').'/v2.php',[
                'apikey' => config('services.numberland.api_key'),
                'method' => 'checkstatus',
                'id' => $order_id,
            ]);

        if (! $response->successful()) {

            Log::error('NumberLand order status check failed', [
                'status' => $response->status(),
                'order_id' => $order_id,
                'body' => $response->body(),
            ]);

            throw new Exception(
                __('sms.unable_to_check_status'),
                1002
            );
        }

        $data = $response->json();

        if (($data['RESULT'] ?? null) == -304) {

            Log::error('NumberLand order not found', [
                'order_id' => $order_id,
                'response' => $data,
            ]);

            throw new Exception(
                __('sms.unable_to_check_status'),
                1002
            );
        }

        $statusMap = [
            1 => 'awaiting_confirmation', // wait code
            2 => 'completed',             // code received
            3 => 'cancelled',             // number canceled
            4 => 'cancelled',             // number banned
            5 => 'awaiting_confirmation', // wait code again
            6 => 'completed',             // completed
        ];

        $status = $statusMap[$data['RESULT']] ?? 'unknown';

        $smsCode = null;

        if (
            (int) $data['RESULT'] === 2 &&
            ! empty($data['CODE']) &&
            $data['CODE'] !== '0'
        ) {
            $smsCode = $data['CODE'];
        }

        return [
            'order_id' => $order_id,
            'order_status' => $status,

            // Returned for compatibility with SmsCodexService
            'sms' => $smsCode
                ? [
                    [
                        'code' => $smsCode,
                        'text' => null,
                        'sender' => null,
                        'received_at' => null,
                    ],
                ]
                : [],

            'last_code' => $smsCode,
        ];
    }
}
