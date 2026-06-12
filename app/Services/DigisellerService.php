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
            "https://api.digiseller.com/api/purchases/unique-code/{$uniqueCode}",
            [
                'token' => $token,
            ]
        );

         Log::info('markAsDelivered', [
            'response' => $response->json(),
        ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to mark purchase as delivered.');
        }

        return $response->json();
    }

    private function getToken()
    {
        $platiToken = PlatiTokens::where('expire_time', '<', now())->value('token');

        if ($platiToken) {
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

            Log::info('token_date',[
                'date' => $data
            ]);

            PlatiTokens::truncate();
            PlatiTokens::create([
                'token' => $data['token'],
                'expire_time' => Carbon::parse($data['valid_thru']),
            ]);

            return $data['token'];
        }
    }
}
