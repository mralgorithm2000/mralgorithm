<?php

namespace App\Console\Commands;

use App\Enums\NumberOrderStatus;
use App\Models\NumberOrder;
use Illuminate\Console\Command;

class ExpireNumberOrders extends Command
{
    protected $signature = 'number-orders:expire';

    protected $description = 'Expire number orders that have passed their expiration time';

    public function handle(): int
    {
        $expiredCount = NumberOrder::query()
            ->whereNull('sms_code')
            ->where('expires_at', '<=', now())
            ->where('status', '!=', NumberOrderStatus::EXPIRED->value)
            ->update([
                'status' => NumberOrderStatus::EXPIRED->value,
            ]);

        $this->info("Expired {$expiredCount} number order(s).");

        return self::SUCCESS;
    }
}
