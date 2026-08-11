<?php

namespace App\Enums;

enum PhoneAttemptStatus: string
{
    case WAITING = 'waiting';
    case RECEIVED = 'received';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::WAITING => 'Waiting',
            self::RECEIVED => 'Received',
            self::EXPIRED => 'Expired',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
        };
    }
}
