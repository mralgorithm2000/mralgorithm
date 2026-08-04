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
        public readonly int $attemptId,
        public readonly string $smsCode,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("phone-attempt.{$this->attemptId}");
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
            'order_id' => $this->attemptId,
            'sms_code' => $this->smsCode,
        ];
    }
}
