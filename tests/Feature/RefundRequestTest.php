<?php

namespace Tests\Feature;

use App\Enums\PhoneAttemptStatus;
use App\Enums\PurchaseStatus;
use App\Models\PhoneAttempt;
use App\Models\Purchase;
use App\Models\VirtualNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_eligible_refund_request_is_created_idempotently(): void
    {
        $purchase = $this->purchaseWithAttempt(now()->subMinute());

        $this->postJson('/api/vm/refund-request', ['uniquecode' => $purchase->unique_code])
            ->assertCreated()
            ->assertJsonPath('refund_status', 'pending')
            ->assertJsonPath('purchase_status', PurchaseStatus::REFUND_PENDING->value);

        $this->postJson('/api/vm/refund-request', ['uniquecode' => $purchase->unique_code])
            ->assertOk()
            ->assertJsonPath('created', false);

        $this->assertDatabaseCount('refund_requests', 1);
        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'status' => PurchaseStatus::REFUND_PENDING->value,
        ]);
    }

    public function test_an_active_attempt_prevents_a_refund_request(): void
    {
        $purchase = $this->purchaseWithAttempt(now()->addMinute());

        $this->postJson('/api/vm/refund-request', ['uniquecode' => $purchase->unique_code])
            ->assertConflict()
            ->assertJsonPath('can_request_refund', false);

        $this->assertDatabaseCount('refund_requests', 0);
    }

    public function test_a_received_code_prevents_a_refund_request(): void
    {
        $purchase = $this->purchaseWithAttempt(now()->subMinute(), '123456');

        $this->postJson('/api/vm/refund-request', ['uniquecode' => $purchase->unique_code])
            ->assertConflict();

        $this->assertDatabaseCount('refund_requests', 0);
    }

    private function purchaseWithAttempt($expiresAt, ?string $smsCode = null): Purchase
    {
        $service = VirtualNumber::create([
            'country' => 'Test',
            'original_price' => '1',
            'source' => 'numberland',
            'type' => 'telegram',
            'plati_id' => 'refund-'.uniqid(),
        ]);

        $purchase = $service->purchases()->create([
            'unique_code' => 'code-'.uniqid(),
            'external_order_id' => 'invoice-'.uniqid(),
            'marketplace' => 'plati',
            'sold_price' => 1,
        ]);

        PhoneAttempt::create([
            'purchase_id' => $purchase->id,
            'provider_order_id' => 'provider-'.uniqid(),
            'provider' => 'numberland',
            'phone_number' => '123456789',
            'country_code' => '+1',
            'sms_code' => $smsCode,
            'status' => $smsCode ? PhoneAttemptStatus::RECEIVED->value : PhoneAttemptStatus::WAITING->value,
            'expires_at' => $expiresAt,
        ]);

        return $purchase;
    }
}
