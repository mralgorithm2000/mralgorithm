<?php

namespace Tests\Feature;

use App\Enums\NumberOrderStatus;
use App\Models\NumberOrder;
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

        $order = NumberOrder::create([
            'virtual_number_id' => $service->id,
            'plati_order_id' => 'invoice-1',
            'phone_number' => '123456789',
            'country_code' => '+1',
            'status' => NumberOrderStatus::WAITING->value,
            'expires_at' => now()->addMinute(),
            'source_order_id' => 'source-1',
        ]);

        $this->mock(NumberlandService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getOrderStatus')
                ->once()
                ->with('source-1')
                ->andReturn(['last_code' => '654321']);
        });

        $this->mock(SmsCodeBroadcastService::class, function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('broadcast')
                ->once()
                ->with($order->id, '654321');
        });

        $this->artisan('numberland:check-order-statuses')
            ->expectsOutput('Checked 1 Numberland order(s); received 1 code(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas('number_orders', [
            'id' => $order->id,
            'sms_code' => '654321',
            'status' => NumberOrderStatus::RECEIVED->value,
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

        NumberOrder::create([
            'virtual_number_id' => $service->id,
            'plati_order_id' => 'invoice-expired',
            'phone_number' => '123456789',
            'country_code' => '+1',
            'status' => NumberOrderStatus::WAITING->value,
            'expires_at' => now()->subSecond(),
            'source_order_id' => 'source-expired',
        ]);

        $this->mock(NumberlandService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('getOrderStatus');
        });

        $this->mock(SmsCodeBroadcastService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('broadcast');
        });

        $this->artisan('numberland:check-order-statuses')
            ->expectsOutput('Checked 0 Numberland order(s); received 0 code(s).')
            ->assertSuccessful();
    }
}
