<?php

namespace App\Services;
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


    public function getNumber($service, $order_id): array {


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

        return [
            'order_id' => $data['ID'],
            'number' => $phoneNumber,
            'country_code' => '+'.$countryCode,
        ];
    }

    public function getOrderStatus(string $order_id): array
    {
        throw new Exception(
            'NumberLand getOrderStatus() is not implemented yet. Please provide the SMS/status endpoint documentation.'
        );
    }
}
