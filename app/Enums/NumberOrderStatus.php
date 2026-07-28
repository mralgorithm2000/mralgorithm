<?php

namespace App\Enums;

enum NumberOrderStatus: string
{
    case WAITING = 'waiting';

    case RECEIVED = 'received';

    case EXPIRED = 'expired';

    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::WAITING => 'Waiting',
            self::RECEIVED => 'Received',
            self::EXPIRED => 'Expired',
            self::REFUNDED => 'Refunded',
        };
    }
}
