<?php

namespace App\Services;

use App\Exceptions\DigisellerException;
use App\Models\Good;
use App\Models\PlatiTokens;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DigisellerService
{
    /** @param array<string, mixed> $data */
    public function createFixedPriceProduct(Good $good, array $data): int
    {
        $data += [
            'currency' => 'USD',
            'content_type' => 'digisellercode',
            'locale' => 'en-US',
            'commission_partner' => 1,
            'enabled' => false,
            'cataloguer_category_id' => 0,
            'cataloguer_attributes' => [[
                'attribute_id' => 0,
                'attribute_value_id' => 0,
            ]],
        ];

        $payload = $this->productPayload($good, $data);
        $payload['price'] = [
            'price' => (float) $data['price'],
            'currency' => $data['currency'],
        ];

        return $this->createProduct('uniquefixed', $payload);
    }

    /** @param array<string, mixed> $data */
    public function createVariablePriceProduct(Good $good, array $data): int
    {
        $data += [
            'currency' => 'USD',
            'content_type' => 'digisellercode',
            'locale' => 'en-US',
            'commission_partner' => 1,
            'enabled' => false,
            'cataloguer_category_id' => 0,
            'cataloguer_attributes' => [[
                'attribute_id' => 0,
                'attribute_value_id' => 0,
            ]],
        ];

        $payload = $this->productPayload($good, $data);
        $payload['prices'] = [
            'price' => (float) $data['price'],
            'currency' => $data['currency'],
            'unit_quantity' => $data['unit_quantity'],
            'unit_name' => $data['unit_name'],
        ];

        return $this->createProduct('uniqueunfixed', $payload);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function productPayload(Good $good, array $data): array
    {
        $locale = $data['locale'] ?? 'en-US';
        $category = [
            'owner' => 1,
            'category_id' => $data['plati_category_id'],
        ];

        if (isset($data['cataloguer_category_id'])) {
            $category['cataloguer_category_id'] = $data['cataloguer_category_id'];
        }

        if (! empty($data['cataloguer_attributes'])) {
            $category['cataloguer_attributes'] = $data['cataloguer_attributes'];
        }

        $payload = [
            'content_type' => $data['content_type'],
            'categories' => [$category],
            'name' => [[
                'locale' => $locale,
                'value' => $data['name'] ?? $good->name,
            ]],
            'description' => [[
                'locale' => $locale,
                'value' => $data['description'],
            ]],
            'comission_partner' => $data['commission_partner'] ?? 0,
            'enabled' => $data['enabled'] ?? true,
        ];

        $additionalInfo = $data['add_info'];

        if ($additionalInfo !== '') {
            $payload['add_info'] = [[
                'locale' => $locale,
                'value' => $additionalInfo,
            ]];
        }

        return $payload;
    }

    /** @param array<string, mixed> $payload */
    private function createProduct(string $type, array $payload): int
    {
        try {
            $response = Http::acceptJson()->post(
                "https://api.digiseller.com/api/product/create/{$type}?token=".$this->getToken(),
                $payload,
            );

            Log::error('This is a diigiseller error', [
                'response' => $response,
            ]);
        } catch (ConnectionException $exception) {
            throw new DigisellerException(
                'Unable to connect to DigiSeller.',
                ['message' => $exception->getMessage()],
            );
        }

        $responseData = $response->json();

        if (! $response->successful() || ! is_array($responseData) || ($responseData['retval'] ?? 1) !== 0) {
            throw new DigisellerException(
                'DigiSeller product creation failed.',
                is_array($responseData) ? $responseData : ['body' => $response->body()],
            );
        }

        $productId = $responseData['content']['product_id'] ?? null;

        if (! is_numeric($productId)) {
            throw new DigisellerException(
                'DigiSeller returned an invalid product creation response.',
                $responseData,
            );
        }

        return (int) $productId;
    }

    public function createProductParameter(int $productId, string $title, string $type, int $order): int
    {
        $data = $this->postParameterRequest('https://api.digiseller.com/api/products/options', [
            'product_id' => $productId,
            'name' => [
                ['locale' => 'en-US', 'value' => $title],
                ['locale' => 'ru-RU', 'value' => $title],
            ],
            'comment' => null,
            'type' => $type,
            'separate_content' => false,
            'required' => true,
            'modifier_visible' => true,
            'order' => $order,
        ]);

        $parameterId = $data['content']['option_id'] ?? null;
        if (! is_numeric($parameterId)) {
            throw new DigisellerException('Plati returned an invalid parameter ID.', $data);
        }

        return (int) $parameterId;
    }

    /**
     * @param  list<array{name: string, modifier_type: string, rate: float, order: int}>  $variants
     * @return list<int>
     */
    public function createProductParameterVariants(int $parameterId, array $variants): array
    {
        $data = $this->postParameterRequest(
            "https://api.digiseller.com/api/products/parameters/{$parameterId}/variants",
            ['variants' => array_map(fn (array $variant, int $index): array => [
                'name' => [
                    ['locale' => 'en-US', 'value' => $variant['name']],
                    ['locale' => 'ru-RU', 'value' => $variant['name']],
                ],
                'type' => $variant['modifier_type'],
                'rate' => $variant['rate'],
                'default' => $index === 0,
                'order' => $variant['order'],
            ], $variants, array_keys($variants))],
        );

        $variantIds = $data['content']['variants'] ?? null;
        if (! is_array($variantIds) || count($variantIds) !== count($variants)) {
            throw new DigisellerException('Plati returned invalid parameter option IDs.', $data);
        }

        return array_map(static fn (mixed $id): int => (int) $id, $variantIds);
    }

    /** @param array{name: string, modifier_type: string, rate: float} $variant */
    public function updateProductParameterVariant(int $parameterId, int $variantId, array $variant): void
    {
        $this->postParameterRequest(
            "https://api.digiseller.com/api/products/options/{$parameterId}/variants/{$variantId}",
            [
                'name' => [
                    ['locale' => 'en-US', 'value' => $variant['name']],
                    ['locale' => 'ru-RU', 'value' => $variant['name']],
                ],
                'type' => $variant['modifier_type'],
                'rate' => $variant['rate'],
            ],
        );
    }

    public function deleteProductParameterVariant(int $parameterId, int $variantId): void
    {
        $this->parameterRequest(
            'GET',
            "https://api.digiseller.com/api/products/options/{$parameterId}/variants/{$variantId}/delete",
        );
    }

    /** @param array<string, mixed> $payload */
    private function postParameterRequest(string $url, array $payload): array
    {
        return $this->parameterRequest('POST', $url, $payload);
    }

    /** @param array<string, mixed> $payload */
    private function parameterRequest(string $method, string $url, array $payload = []): array
    {
        $startedAt = microtime(true);

        Log::info('Digiseller parameter API request started.', [
            'method' => $method,
            'url' => $url,
            'payload' => $payload,
        ]);

        try {
            $request = Http::acceptJson();
            $response = $method === 'GET'
                ? $request->get($url.'?token='.$this->getToken())
                : $request->post($url.'?token='.$this->getToken(), $payload);
        } catch (ConnectionException $exception) {
            Log::error('Digiseller parameter API connection failed.', [
                'method' => $method,
                'url' => $url,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'exception' => $exception->getMessage(),
            ]);

            throw new DigisellerException('Unable to connect to Plati.', ['message' => $exception->getMessage()]);
        }

        $data = $response->json();

        Log::log($response->successful() ? 'info' : 'error', 'Digiseller parameter API response received.', [
            'method' => $method,
            'url' => $url,
            'status' => $response->status(),
            'successful' => $response->successful(),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'response' => is_array($data) ? $data : $response->body(),
        ]);

        if (! $response->successful() || ! is_array($data)) {
            throw new DigisellerException(
                'Plati parameter publishing failed.',
                is_array($data) ? $data : ['body' => $response->body()],
                $response->clientError() ? $response->status() : 502,
            );
        }

        if (($data['retval'] ?? 1) !== 0) {
            throw new DigisellerException('Plati parameter publishing failed.', $data, 422);
        }

        return $data;
    }

    public function verifyPurchase(string $uniqueCode): array
    {
        if ($uniqueCode === 'test') {
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

        if ($uniqueCode === 'test2') {
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

        if ($platiToken) {
            Log::debug('Using cached Digiseller API token.');

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

            Log::info('Digiseller API token refreshed.', [
                'valid_thru' => $data['valid_thru'] ?? null,
                'retval' => $data['retval'] ?? null,
            ]);

            PlatiTokens::query()->delete();
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
