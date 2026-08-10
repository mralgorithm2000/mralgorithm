<?php

namespace Tests\Feature;

use App\Enums\PhoneAttemptStatus;
use App\Models\PhoneAttempt;
use App\Models\Purchase;
use App\Models\VirtualNumber;
use App\Services\NumberlandService;
use App\Services\SmsCodeBroadcastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class CheckNumberlandOrderStatusesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_receives_and_broadcasts_codes_for_active_numberland_orders(): void
    {
        $service = VirtualNumber::create([
            'country' => 'Test',
            'original_price' => '1',
            'source' => 'numberland',
            'type' => 'telegram',
            'plati_id' => 'numberland-test',
        ]);

        $purchase = $service->purchases()->create([
            'unique_code' => 'code-1',
            'external_order_id' => 'invoice-1',
            'marketplace' => 'plati',
            'sold_price' => 1,
        ]);
        $attempt = PhoneAttempt::create([
            'purchase_id' => $purchase->id,
            'phone_number' => '123456789',
            'country_code' => '+1',
            'status' => PhoneAttemptStatus::WAITING->value,
            'expires_at' => now()->addMinute(),
            'provider_order_id' => 'source-1',
            'provider' => 'numberland',
        ]);

        $this->mock(NumberlandService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getOrderStatus')
                ->once()
                ->with('source-1')
                ->andReturn(['last_code' => '654321']);
        });

        $this->mock(SmsCodeBroadcastService::class, function (MockInterface $mock) use ($attempt) {
            $mock->shouldReceive('broadcast')
                ->once()
                ->with($attempt->id, '654321');
        });

        $this->artisan('numberland:check-order-statuses')
            ->expectsOutput('Checked 1 Numberland attempt(s); received 1 code(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas('phone_attempts', [
            'id' => $attempt->id,
            'sms_code' => '654321',
            'status' => PhoneAttemptStatus::RECEIVED->value,
        ]);
    }

    public function test_it_does_not_check_expired_orders(): void
    {
        $service = VirtualNumber::create([
            'country' => 'Test',
            'original_price' => '1',
            'source' => 'numberland',
            'type' => 'telegram',
            'plati_id' => 'numberland-expired',
        ]);

        $purchase = $service->purchases()->create([
            'unique_code' => 'code-expired',
            'external_order_id' => 'invoice-expired',
            'marketplace' => 'plati',
            'sold_price' => 1,
        ]);
        PhoneAttempt::create([
            'purchase_id' => $purchase->id,
            'phone_number' => '123456789',
            'country_code' => '+1',
            'status' => PhoneAttemptStatus::WAITING->value,
            'expires_at' => now()->subSecond(),
            'provider_order_id' => 'source-expired',
            'provider' => 'numberland',
        ]);

        $this->mock(NumberlandService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('getOrderStatus');
        });

        $this->mock(SmsCodeBroadcastService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('broadcast');
        });

        $this->artisan('numberland:check-order-statuses')
            ->expectsOutput('Checked 0 Numberland attempt(s); received 0 code(s).')
            ->assertSuccessful();
    }
}
