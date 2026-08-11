<?php

namespace Tests\Feature;

use App\Enums\PhoneAttemptStatus;
use App\Enums\PurchaseStatus;
use App\Enums\RefundRequestStatus;
use App\Models\PhoneAttempt;
use App\Models\Purchase;
use App\Models\VirtualNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReplacementPhoneNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_eligible_purchase_can_order_a_replacement_number(): void
    {
        $purchase = $this->purchaseWithAttempt(PhoneAttemptStatus::CANCELLED);

        Http::fake([
            '*' => Http::response([
                'order_id' => 'replacement-provider-order',
                'phone_number' => '+15559876543',
                'expires_at' => now()->addMinutes(15)->toIso8601String(),
                'price' => 0.20,
            ]),
        ]);

        $this->postJson('/api/vm/replacement', ['uniquecode' => $purchase->external_order_id])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.number', '5559876543')
            ->assertJsonPath('can_order_replacement', false);

        $this->assertDatabaseHas('phone_attempts', [
            'purchase_id' => $purchase->id,
            'provider_order_id' => 'replacement-provider-order',
            'status' => PhoneAttemptStatus::WAITING->value,
        ]);
    }

    public function test_an_active_attempt_blocks_a_replacement(): void
    {
        $purchase = $this->purchaseWithAttempt(PhoneAttemptStatus::WAITING, now()->addMinutes(5));

        $this->postJson('/api/vm/replacement', ['uniquecode' => $purchase->external_order_id])
            ->assertConflict()
            ->assertJsonPath('message', __('sms.replacement_active_attempt'));
    }

    public function test_any_received_code_blocks_a_replacement(): void
    {
        $purchase = $this->purchaseWithAttempt(PhoneAttemptStatus::RECEIVED, now()->subMinute(), '123456');

        $this->postJson('/api/vm/replacement', ['uniquecode' => $purchase->external_order_id])
            ->assertConflict()
            ->assertJsonPath('message', __('sms.replacement_code_received'));
    }

    public function test_a_refunded_purchase_blocks_a_replacement(): void
    {
        $purchase = $this->purchaseWithAttempt(PhoneAttemptStatus::REFUNDED);
        $purchase->update(['status' => PurchaseStatus::REFUNDED->value]);

        $this->postJson('/api/vm/replacement', ['uniquecode' => $purchase->external_order_id])
            ->assertConflict()
            ->assertJsonPath('message', __('sms.replacement_purchase_refunded'));
    }

    public function test_an_active_refund_request_blocks_a_replacement(): void
    {
        $purchase = $this->purchaseWithAttempt(PhoneAttemptStatus::CANCELLED);
        $purchase->refundRequest()->create([
            'status' => RefundRequestStatus::PENDING->value,
            'requested_at' => now(),
        ]);

        $this->postJson('/api/vm/replacement', ['uniquecode' => $purchase->external_order_id])
            ->assertConflict()
            ->assertJsonPath('message', __('sms.replacement_refund_active'));
    }

    private function purchaseWithAttempt(
        PhoneAttemptStatus $status,
        mixed $expiresAt = null,
        ?string $smsCode = null,
    ): Purchase {
        $service = VirtualNumber::create([
            'country' => 'US',
            'original_price' => '0.23',
            'source' => 'smscodex',
            'type' => 'telegram',
            'plati_id' => 'replacement-'.uniqid(),
            'service_id' => 'tg',
            'country_id' => 'US',
            'country_code' => '1',
        ]);

        $purchase = $service->purchases()->create([
            'marketplace' => 'plati',
            'external_order_id' => uniqid('invoice-'),
            'sold_price' => 0.33,
            'status' => PurchaseStatus::PENDING->value,
        ]);

        PhoneAttempt::create([
            'purchase_id' => $purchase->id,
            'provider_order_id' => uniqid('provider-'),
            'provider' => 'smscodex',
            'phone_number' => '5551234567',
            'country_code' => '+1',
            'sms_code' => $smsCode,
            'status' => $status->value,
            'expires_at' => $expiresAt ?? now()->subMinute(),
        ]);

        return $purchase;
    }
}
