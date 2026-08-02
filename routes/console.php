<?php

use App\Console\Commands\CheckNumberlandOrderStatuses;
use App\Console\Commands\ExpireNumberOrders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(ExpireNumberOrders::class)->everyMinute();
Schedule::command(CheckNumberlandOrderStatuses::class)
    ->everyFiveSeconds()
    ->withoutOverlapping();
