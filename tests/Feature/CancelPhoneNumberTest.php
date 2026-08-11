<?php

namespace Tests\Feature;

use App\Enums\PhoneAttemptStatus;
use App\Models\PhoneAttempt;
use App\Models\Purchase;
use App\Models\VirtualNumber;
use App\Services\SmsCodexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelPhoneNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_waiting_smscodex_number_can_be_canceled(): void
    {
        [$purchase, $attempt] = $this->waitingAttempt();

        $this->mock(SmsCodexService::class, function ($mock) use ($attempt) {
            $mock->shouldReceive('cancelOrder')
                ->once()
                ->with($attempt->provider_order_id)
                ->andReturn(['order_status' => SmsCodexService::ORDER_STATUS_CANCELED]);
        });

        $this->postJson('/api/vm/cancel-number', ['uniqueCode' => $purchase->external_order_id])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', PhoneAttemptStatus::EXPIRED->value)
            ->assertJsonPath('can_order_replacement', true)
            ->assertJsonPath('can_request_refund', true);

        $this->assertDatabaseHas('phone_attempts', [
            'id' => $attempt->id,
            'status' => PhoneAttemptStatus::EXPIRED->value,
        ]);
    }

    public function test_attempt_stays_waiting_when_smscodex_does_not_confirm_cancellation(): void
    {
        [$purchase, $attempt] = $this->waitingAttempt();

        $this->mock(SmsCodexService::class, function ($mock) {
            $mock->shouldReceive('cancelOrder')
                ->once()
                ->andReturn(['order_status' => 'waiting']);
        });

        $this->postJson('/api/vm/cancel-number', ['uniqueCode' => $purchase->external_order_id])
            ->assertConflict()
            ->assertJsonPath('success', false);

        $this->assertSame(PhoneAttemptStatus::WAITING->value, $attempt->fresh()->status);
    }

    private function waitingAttempt(): array
    {
        $service = VirtualNumber::create([
            'country' => 'US',
            'original_price' => '0.23',
            'source' => 'smscodex',
            'type' => 'telegram',
            'plati_id' => 'cancel-'.uniqid(),
            'service_id' => 'tg',
            'country_id' => 'US',
            'country_code' => '1',
        ]);

        $purchase = $service->purchases()->create([
            'marketplace' => 'plati',
            'external_order_id' => uniqid('invoice-'),
            'sold_price' => 0.33,
        ]);

        $attempt = PhoneAttempt::create([
            'purchase_id' => $purchase->id,
            'provider_order_id' => uniqid('provider-'),
            'provider' => 'smscodex',
            'phone_number' => '5551234567',
            'country_code' => '+1',
            'status' => PhoneAttemptStatus::WAITING->value,
            'expires_at' => now()->addMinutes(5),
        ]);

        return [$purchase, $attempt];
    }
}
