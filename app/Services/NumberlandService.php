<?php

namespace App\Services;

use App\Models\PhoneAttempt;
use App\Models\Purchase;
use App\Models\VirtualNumber;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery\CountValidator\Exception;

class NumberlandService
{
    /**
     * NumberLand Service IDs.
     *
     * Replace these IDs with the correct values from the getinfo API.
     */
    public function getNumber(VirtualNumber $service, Purchase $purchase): PhoneAttempt
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

        if (
            $countryCode !== '' &&
            str_starts_with($phoneNumber, $countryCode)
        ) {
            $phoneNumber = substr(
                $phoneNumber,
                strlen($countryCode)
            );
        }

        return $purchase->phoneAttempts()->create([
            'provider_order_id' => $data['ID'],
            'provider' => 'numberland',
            'phone_number' => $phoneNumber,
            'country_code' => '+'.$countryCode,
            'expires_at' => Carbon::now()->addMinutes(20),
        ]);
    }

    public function getOrderStatus(string $order_id): array
    {

        $url = config('services.numberland.base_url').'/v2.php'
    .'?apikey='.urlencode(config('services.numberland.api_key'))
    .'&method=checkstatus'
    .'&id='.urlencode($order_id);

        $response = Http::timeout(30)
            ->connectTimeout(30)
            ->get($url);

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
