<?php

namespace Tests\Feature;

use App\Enums\PhoneAttemptStatus;
use App\Models\PhoneAttempt;
use App\Models\VirtualNumber;
use App\Services\SmsCodexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelPhoneNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_waiting_smscodex_cancellation_request_is_sent_without_expiring_the_attempt(): void
    {
        [$purchase, $attempt] = $this->waitingAttempt();

        $this->mock(SmsCodexService::class, function ($mock) use ($attempt) {
            $mock->shouldReceive('cancelOrder')
                ->once()
                ->with($attempt->provider_order_id)
                ->andReturn(['success' => true]);
        });

        $this->postJson('/api/vm/cancel-number', ['uniqueCode' => $purchase->external_order_id])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', __('sms.cancellation_request_sent'));

        $this->assertDatabaseHas('phone_attempts', [
            'id' => $attempt->id,
            'status' => PhoneAttemptStatus::WAITING->value,
        ]);
    }

    public function test_attempt_stays_waiting_when_smscodex_accepts_cancellation_asynchronously(): void
    {
        [$purchase, $attempt] = $this->waitingAttempt();

        $this->mock(SmsCodexService::class, function ($mock) {
            $mock->shouldReceive('cancelOrder')
                ->once()
                ->andReturn(['order_status' => 'waiting']);
        });

        $this->postJson('/api/vm/cancel-number', ['uniqueCode' => $purchase->external_order_id])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(PhoneAttemptStatus::WAITING->value, $attempt->fresh()->status);
    }

    public function test_a_number_cannot_be_cancelled_during_its_first_three_minutes(): void
    {
        [$purchase] = $this->waitingAttempt(false);

        $this->postJson('/api/vm/cancel-number', ['uniqueCode' => $purchase->external_order_id])
            ->assertConflict()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('sms.cancellation_not_available_yet'))
            ->assertJsonStructure(['cancel_available_in']);
    }

    private function waitingAttempt(bool $cancellationAvailable = true): array
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

        if ($cancellationAvailable) {
            $attempt->forceFill(['created_at' => now()->subMinutes(3)])->saveQuietly();
        }

        return [$purchase, $attempt];
    }
}
