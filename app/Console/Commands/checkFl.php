<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Jobs\checkFL as checkFLJob;
#[Signature('app:check-fl')]
#[Description('Command description')]
class checkFl extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        checkFLJob::dispatch();
    }
}
