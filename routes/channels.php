<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('number-order.{orderId}', function ($user, int $orderId): bool {
    return (bool) request()->session()->get("number_order_ids.{$orderId}", false);
}, ['guards' => ['order-session']]);
