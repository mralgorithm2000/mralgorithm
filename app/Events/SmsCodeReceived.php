<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SmsCodeReceived implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $orderId,
        public readonly string $smsCode,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("number-order.{$this->orderId}");
    }

    public function broadcastAs(): string
    {
        return 'sms.code.received';
    }

    /**
     * @return array{order_id: int, sms_code: string}
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'sms_code' => $this->smsCode,
        ];
    }
}
