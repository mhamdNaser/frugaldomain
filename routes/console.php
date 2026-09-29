<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Shared hosting has no long-running queue worker, so the scheduler drains the
// queue (Shopify import jobs, webhooks) every minute. Needs one cron entry:
//   * * * * * cd /path/to/api && php artisan schedule:run >> /dev/null 2>&1
// Jobs use named queues (onQueue('shopify-orders') etc.); a worker only drains
// the queues it is given, so every one must be listed here. Order = priority.
$queues = implode(',', [
    'default',
    'shopify-sync',
    'shopify-outbound',
    'shopify-orders',
    'shopify-customers',
    'shopify-inventory',
    'shopify-variants',
    'shopify-collections',
    'shopify-draft-orders',
    'shopify-fulfillments',
    'shopify-financials',
    'shopify-discounts',
    'shopify-content',
    'shopify-files',
    'shopify-metafields',
    'shopify-images',
    'shopify-variant-images',
]);

Schedule::command("queue:work --queue={$queues} --stop-when-empty --max-time=50 --tries=3")
    ->everyMinute()
    ->withoutOverlapping(10);
