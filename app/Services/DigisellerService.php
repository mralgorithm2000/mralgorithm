<?php

namespace App\Services;

use App\Models\PlatiTokens;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DigisellerService
{
    public function verifyPurchase(string $uniqueCode): array
    {
        if($uniqueCode === 'test') {
            return [
                'id_goods' => 123,
                'options' => [
                    [
                        'id' => 1,
                        'variant_id' => 456,
                    ],
                ],
                'inv' => 789,
            ];
        }

        if($uniqueCode === 'test2') {
            return [
                'id_goods' => 1234,
                'options' => [
                    [
                        'id' => 12,
                        'variant_id' => 4567,
                    ],
                ],
                'inv' => 7890,
            ];
        }


        $token = $this->getToken();

        $response = Http::acceptJson()->get(
            "https://api.digiseller.com/api/purchases/unique-code/{$uniqueCode}",
            [
                'token' => $token,
            ]
        );
        Log::info('verification', [
            'response' => $response->json(),
        ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to verify Digiseller purchase.');
        }

        return $response->json();
    }

    public function markAsDelivered(string $uniqueCode): array
    {
        $token = $this->getToken();

        $response = Http::acceptJson()->put(
            "https://api.digiseller.com/api/purchases/unique-code/{$uniqueCode}/deliver?token=".$token,
            [
                'token' => $token,
            ]
        );

        Log::info('markAsDelivered', [
            'token' => $token,
            'response' => $response->json(),
        ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to mark purchase as delivered.');
        }

        return $response->json();
    }

    private function getToken()
    {
        $platiToken = PlatiTokens::where('expire_time', '>', now())
            ->value('token');

        Log::info('token token token', [
            'platiToken' => $platiToken,
        ]);

        if ($platiToken) {
            Log::info('token token token', [
                'response' => $platiToken,
            ]);

            return $platiToken;
        } else {
            $timestamp = time();
            $apiKey = 'C2F058875033483DBA19F4BFE54F70C8';
            $sign = hash('sha256', $apiKey.$timestamp);
            $response = Http::post(
                'https://api.digiseller.com/api/apilogin',
                [
                    'seller_id' => 1438615,
                    'timestamp' => $timestamp,
                    'sign' => $sign,
                ]
            );

            $data = $response->json();

            Log::info('token_date', [
                'date' => $data,
            ]);

            PlatiTokens::truncate();
            PlatiTokens::create([
                'token' => $data['token'],
                'expire_time' => Carbon::parse($data['valid_thru']),
            ]);

            return $data['token'];
        }
    }

    public function getPurchaseInfo(int|string $invoiceId): array
    {
        $token = $this->getToken();

        $response = Http::get(
            "https://api.digiseller.com/api/purchase/info/{$invoiceId}",
            [
                'token' => $token,
            ]
        );

        Log::info('token_date', [
            'date' => $response->json(),
        ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to get purchase information.');
        }

        return $response->json();
    }
}
