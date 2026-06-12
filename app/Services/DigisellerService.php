<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DigisellerService
{
    public function verifyPurchase(string $uniqueCode): array
    {
        $token = env('DIGISELLER_TOKEN');

        $response = Http::acceptJson()->get(
            "https://api.digiseller.com/api/purchases/unique-code/{$uniqueCode}",
            [
                'token' => $token,
            ]
        );

        if (! $response->successful()) {
            throw new \Exception('Failed to verify Digiseller purchase.');
        }

        return $response->json();
    }

    public function markAsDelivered(string $uniqueCode): array
    {
        $token = env('DIGISELLER_TOKEN');

        $response = Http::acceptJson()->put(
            "https://api.digiseller.com/api/purchases/unique-code/{$uniqueCode}",
            [
                'token' => $token,
            ]
        );

        if (! $response->successful()) {
            throw new \Exception('Failed to mark purchase as delivered.');
        }

        return $response->json();
    }
}
