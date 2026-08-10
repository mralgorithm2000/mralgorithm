<?php

namespace Tests\Feature;

use App\Models\VirtualNumber;
use App\Services\SmsCodexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsCodexSupplierCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_paid_attempts_sum_confirmed_supplier_costs(): void
    {
        Http::fakeSequence()
            ->push($this->success('provider-1', '0.125'))
            ->push($this->success('provider-2', '0.130'));

        [$service, $purchase] = $this->purchase();
        $supplier = app(SmsCodexService::class);
        $supplier->getNumber($service, $purchase);
        $supplier->getNumber($service, $purchase);

        $this->assertSame('0.255000', $purchase->fresh()->cost_price);
        $this->assertCount(2, $purchase->phoneAttempts);
    }

    public function test_rejected_price_limit_does_not_record_a_cost_or_attempt(): void
    {
        Http::fake([
            '*' => Http::response(['detail' => [
                'error' => 'price_limit_exceeded', 'limit' => '0.23', 'price' => '0.3044',
            ]], 422),
        ]);

        [$service, $purchase] = $this->purchase();

        try {
            app(SmsCodexService::class)->getNumber($service, $purchase);
            $this->fail('Expected the rejected supplier request to throw.');
        } catch (\Exception) {
            $this->assertSame('0.000000', $purchase->fresh()->cost_price);
            $this->assertDatabaseCount('phone_attempts', 0);
        }
    }

    private function purchase(): array
    {
        $service = VirtualNumber::create([
            'country' => 'US', 'original_price' => '0.23', 'source' => 'smscodex',
            'type' => 'telegram', 'plati_id' => 'supplier-test', 'service_id' => 'tg',
            'country_id' => 'US', 'country_code' => '1',
        ]);
        $purchase = $service->purchases()->create([
            'marketplace' => 'plati', 'external_order_id' => uniqid('invoice-'),
            'unique_code' => uniqid('code-'), 'sold_price' => 0.33,
            'marketplace_fee' => 0.02,
        ]);

        return [$service, $purchase];
    }

    private function success(string $orderId, string $price): array
    {
        return [
            'order_id' => $orderId, 'phone_number' => '+15551234567',
            'expires_at' => now()->addMinutes(10)->toIso8601String(), 'price' => $price,
        ];
    }
}
