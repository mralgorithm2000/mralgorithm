<?php

namespace Tests\Feature;

use App\Enums\PhoneAttemptStatus;
use App\Enums\PurchaseStatus;
use App\Models\Option;
use App\Models\VirtualNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RetryIncompleteVirtualNumberPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_orders_a_number_for_an_existing_purchase_with_no_attempts(): void
    {
        Option::create([
            'plati_id' => 123,
            'option_id' => '1',
            'title' => 'Country',
            'type' => 'country',
        ]);

        $service = VirtualNumber::create([
            'country' => 'US',
            'original_price' => '0.23',
            'source' => 'smscodex',
            'type' => 'telegram',
            'plati_id' => '456',
            'provider_id' => 'provider',
            'service_id' => 'tg',
            'country_id' => 'US',
            'country_code' => '1',
        ]);

        $purchase = $service->purchases()->create([
            'marketplace' => 'plati',
            'external_order_id' => 'test',
            'sold_price' => 1,
            'status' => PurchaseStatus::PENDING->value,
        ]);

        Http::fake([
            '*' => Http::response([
                'order_id' => 'retried-provider-order',
                'phone_number' => '+15551234567',
                'expires_at' => now()->addMinutes(15)->toIso8601String(),
                'price' => 0.20,
            ]),
        ]);

        $this->postJson('/api/vm/verify', ['uniquecode' => 'test'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.number', '5551234567');

        $this->assertDatabaseHas('phone_attempts', [
            'purchase_id' => $purchase->id,
            'provider_order_id' => 'retried-provider-order',
            'status' => PhoneAttemptStatus::WAITING->value,
        ]);
    }
}
