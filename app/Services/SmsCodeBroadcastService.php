<?php

namespace App\Services;

use App\Events\SmsCodeReceived;

class SmsCodeBroadcastService
{
    public function broadcast(int $orderId, string $smsCode): void
    {
        SmsCodeReceived::dispatch($orderId, $smsCode);
    }
}
