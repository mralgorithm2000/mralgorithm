<?php

namespace App\Services;

use App\Events\SmsCodeReceived;

class SmsCodeBroadcastService
{
    public function broadcast(int $attemptId, string $smsCode): void
    {
        SmsCodeReceived::dispatch($attemptId, $smsCode);
    }
}
