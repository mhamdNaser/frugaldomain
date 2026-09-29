<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Shared hosting has no long-running queue worker, so the scheduler drains the
// queue (Shopify import jobs, webhooks) every minute. Needs one cron entry:
//   * * * * * cd /path/to/api && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping(10);
