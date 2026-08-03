<?php

namespace App\Console\Commands;

use App\Enums\NumberOrderStatus;
use App\Models\NumberOrder;
use App\Services\NumberlandService;
use App\Services\SmsCodeBroadcastService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckNumberlandOrderStatuses extends Command
{
    protected $signature = 'numberland:check-order-statuses';

    protected $description = 'Check active Numberland orders for received SMS codes';

    public function handle(
        NumberlandService $numberlandService,
        SmsCodeBroadcastService $smsCodeBroadcastService
    ): int {
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
                $numberlandService,
                $smsCodeBroadcastService,
                &$checkedCount,
                &$receivedCount
            ) {
                $checkedCount++;

                try {
                    $status = $numberlandService->getOrderStatus($order->source_order_id);
                    $smsCode = $status['last_code']
                        ?? data_get($status, 'sms.0.code');

                    if (! $smsCode) {
                        return;
                    }

                    $updated = NumberOrder::query()
                        ->whereKey($order->getKey())
                        ->where('status', NumberOrderStatus::WAITING->value)
                        ->whereNull('sms_code')
                        ->update([
                            'sms_code' => (string) $smsCode,
                            'status' => NumberOrderStatus::RECEIVED->value,
                        ]);

                    if (! $updated) {
                        return;
                    }

                    $receivedCount++;
                    $smsCodeBroadcastService->broadcast($order->id, (string) $smsCode);
                } catch (Throwable $exception) {
                    Log::error('NumberLand scheduled order status check failed', [
                        'number_order_id' => $order->id,
                        'source_order_id' => $order->source_order_id,
                        'exception' => $exception->getMessage(),
                    ]);
                }
            });

        $this->info("Checked {$checkedCount} Numberland order(s); received {$receivedCount} code(s).");

        return self::SUCCESS;
    }
}
