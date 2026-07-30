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
    private const COUNTRY_CODES = [
        'Georgia' => [
            'smscodex_id' => '128',
            'country_code' => '996',
        ],
        'Kyrgyzstan' => [
            'smscodex_id' => '11',
            'country_code' => '996'
        ],
        'Kazakhstan' => [
            'smscodex_id' => '2',
            'country_code' => '7'
        ]
    ];

    private const SERVICE_CODES = [
        'Telegram' => 'tg',
    ];

    public function getNumber(string $country, string $service_type, $original_price): array
    {

        $country = self::COUNTRY_CODES[$country];

        $response = Http::withToken(config('services.smscodex.api_key'))
        ->withHeader('X-API-Key',config('services.smscodex.api_key'))
            ->post(
                config('services.smscodex.base_url') . '/api/v1/marketplace/fast-purchase/api',
                [
                    'service_code' => self::SERVICE_CODES[$service_type],
                    'country' => $country['smscodex_id'],
                    'operator' => 'any',
                    'price_limit' => $original_price,
                    'extras' => [
                        'priority' => 'quality',
                    ],
                ]
            );

        if (! $response->successful()) {
            Log::error('SMSCodex purchase failed', [
                'status' => $response->status(),
                'service_code' => self::SERVICE_CODES[$service_type],
                'country' => $country['smscodex_id'],
                'original_price' => $original_price,
                'body' => $response->body(),
            ]);

            throw new Exception('Unable to purchase phone number.');
        }

        $data = $response->json();

        $countryCode = $country['country_code'];

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
}
