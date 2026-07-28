<?php

namespace App\Enums;

enum NumberOrderStatus: string
{
    case WAITING = 'waiting';

    case RECEIVED = 'received';

    case EXPIRED = 'expired';

    case REFUNDED = 'refunded';
}
