<?php

namespace Tests\Feature;

use App\Enums\PhoneAttemptStatus;
use App\Events\PhoneNumberCancelled;
use App\Models\PhoneAttempt;
use App\Models\VirtualNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SmsCodexCancellationWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelled_webhook_expires_attempt_and_broadcasts_to_frontend(): void
    {
        Event::fake([PhoneNumberCancelled::class]);

        $service = VirtualNumber::create([
            'country' => 'US',
            'original_price' => '0.23',
            'source' => 'smscodex',
            'type' => 'telegram',
            'plati_id' => 'webhook-'.uniqid(),
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
            'provider_order_id' => 'provider-cancelled-123',
            'provider' => 'smscodex',
            'phone_number' => '5551234567',
            'country_code' => '+1',
            'status' => PhoneAttemptStatus::WAITING->value,
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->postJson('/api/sms/webhook/smscodex', [
            'payload' => [
                'stage' => 'cancelled',
                'order_id' => $attempt->provider_order_id,
            ],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame(PhoneAttemptStatus::EXPIRED->value, $attempt->fresh()->status);

        Event::assertDispatched(
            PhoneNumberCancelled::class,
            fn (PhoneNumberCancelled $event) => $event->attemptId === $attempt->id
                && $event->canOrderReplacement
                && $event->canRequestRefund,
        );
    }
}
