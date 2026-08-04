<?php

namespace App\Enums;

enum PurchaseStatus: string
{
    case PENDING = 'pending';
    case REFUND_PENDING = 'refund_pending';
    case COMPLETED = 'completed';
    case REFUNDED = 'refunded';
}
