<?php

namespace App\Events;

use App\Enums\PhoneAttemptStatus;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PhoneNumberCancelled implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $attemptId,
        public readonly bool $canOrderReplacement,
        public readonly bool $canRequestRefund,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("phone-attempt.{$this->attemptId}");
    }

    public function broadcastAs(): string
    {
        return 'phone.number.cancelled';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->attemptId,
            'status' => PhoneAttemptStatus::CANCELLED->value,
            'message' => __('sms.number_canceled'),
            'can_order_replacement' => $this->canOrderReplacement,
            'can_request_refund' => $this->canRequestRefund,
        ];
    }
}
