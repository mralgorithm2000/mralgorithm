<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('phone-attempt.{attemptId}', function ($user, int $attemptId): bool {
    return (bool) request()->session()->get("phone_attempt_ids.{$attemptId}", false);
}, ['guards' => ['order-session']]);
