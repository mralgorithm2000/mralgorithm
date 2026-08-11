<?php

namespace App\Console\Commands;

use App\Enums\PhoneAttemptStatus;
use App\Models\PhoneAttempt;
use Illuminate\Console\Command;

class ExpirePhoneAttempts extends Command
{
    protected $signature = 'phone-attempts:expire';

    protected $description = 'Expire phone attempts that have passed their expiration time';

    public function handle(): int
    {
        $expiredCount = PhoneAttempt::query()
            ->whereNull('sms_code')
            ->where('expires_at', '<=', now())
            ->where('status', PhoneAttemptStatus::WAITING->value)
            ->update(['status' => PhoneAttemptStatus::EXPIRED->value]);

        $this->info("Expired {$expiredCount} phone attempt(s).");

        return self::SUCCESS;
    }
}
