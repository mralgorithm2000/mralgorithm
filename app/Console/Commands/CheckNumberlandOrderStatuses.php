<?php

namespace App\Console\Commands;

use App\Enums\PhoneAttemptStatus;
use App\Enums\PurchaseStatus;
use App\Models\PhoneAttempt;
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

        PhoneAttempt::query()
            ->where('status', PhoneAttemptStatus::WAITING->value)
            ->whereNull('sms_code')
            ->whereNotNull('provider_order_id')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where('provider', 'numberland')
            ->lazyById()
            ->each(function (PhoneAttempt $attempt) use (
                $numberlandService,
                $smsCodeBroadcastService,
                &$checkedCount,
                &$receivedCount
            ) {
                $checkedCount++;

                try {
                    $status = $numberlandService->getOrderStatus($attempt->provider_order_id);
                    $smsCode = $status['last_code']
                        ?? data_get($status, 'sms.0.code');

                    if (! $smsCode) {
                        return;
                    }

                    $updated = PhoneAttempt::query()
                        ->whereKey($attempt->getKey())
                        ->where('status', PhoneAttemptStatus::WAITING->value)
                        ->whereNull('sms_code')
                        ->update([
                            'sms_code' => (string) $smsCode,
                            'status' => PhoneAttemptStatus::RECEIVED->value,
                        ]);

                    if (! $updated) {
                        return;
                    }

                    $attempt->purchase()->update([
                        'status' => PurchaseStatus::COMPLETED->value,
                    ]);

                    $receivedCount++;
                    $smsCodeBroadcastService->broadcast($attempt->id, (string) $smsCode);
                } catch (Throwable $exception) {
                    Log::error('NumberLand scheduled order status check failed', [
                        'phone_attempt_id' => $attempt->id,
                        'provider_order_id' => $attempt->provider_order_id,
                        'exception' => $exception->getMessage(),
                    ]);
                }
            });

        $this->info("Checked {$checkedCount} Numberland attempt(s); received {$receivedCount} code(s).");

        return self::SUCCESS;
    }
}
