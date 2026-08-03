<?php

namespace App\Console\Commands;

use App\Enums\NumberOrderStatus;
use App\Models\NumberOrder;
use App\Services\NumberlandService;
use App\Services\SmsCodeBroadcastService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckNumberlandOrderStatuses extends Command
{
    protected $signature = 'numberland:check-order-statuses';

    protected $description = 'Check active Numberland orders for received SMS codes';

    public function handle(): int {
        $checkedCount = 0;
        $receivedCount = 0;

        NumberOrder::query()
            ->where('status', NumberOrderStatus::WAITING->value)
            ->whereNull('sms_code')
            ->whereNotNull('source_order_id')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->whereHas('virtualNumber', function ($query) {
                $query->where('source', 'numberland');
            })
            ->lazyById()
            ->each(function (NumberOrder $order) use (
                &$checkedCount,
                &$receivedCount
            ) {
                $checkedCount++;

                Http::get(url('sms/check/numberland',[
                    'order_id' => $order->source_order_id
                ]));
            });

        $this->info("Checked {$checkedCount} Numberland order(s); received {$receivedCount} code(s).");

        return self::SUCCESS;
    }
}
