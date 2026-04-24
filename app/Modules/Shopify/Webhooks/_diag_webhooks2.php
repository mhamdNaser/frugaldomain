<?php
require __DIR__ . '/../../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Modules\Stores\Models\Store;

$domain = 'quickstart-a2f667fa.myshopify.com';
$store = Store::query()->where('shopify_domain', $domain)->first();

if (!$store) {
    echo json_encode(['error' => 'STORE_NOT_FOUND', 'domain' => $domain], JSON_PRETTY_PRINT);
    exit;
}

$result = [
    'store' => [
        'id' => $store->id,
        'owner_id' => $store->owner_id,
        'shopify_domain' => $store->shopify_domain,
        'secret_len' => strlen((string) $store->shopify_webhook_secret),
        'token_len' => strlen((string) $store->shopify_access_token),
        'updated_at' => (string) $store->updated_at,
    ],
    'webhook_logs_count' => DB::table('webhook_logs')->where('store_id', $store->id)->count(),
    'webhook_logs_latest' => DB::table('webhook_logs')
        ->where('store_id', $store->id)
        ->orderByDesc('id')
        ->limit(5)
        ->get(['id','topic','status','attempts','error_message','received_at','processed_at']),
    'jobs_pending_by_queue' => DB::table('jobs')
        ->select('queue', DB::raw('COUNT(*) as c'))
        ->groupBy('queue')
        ->orderByDesc('c')
        ->limit(10)
        ->get(),
    'sync_runs_recent' => DB::table('sync_runs')
        ->where('store_id', $store->id)
        ->orderByDesc('id')
        ->limit(8)
        ->get(['id','type','status','fetched_count','synced_count','failed_count','error_message','created_at','updated_at']),
    'failed_jobs_recent' => DB::table('failed_jobs')
        ->orderByDesc('id')
        ->limit(5)
        ->get(['id','queue','failed_at']),
];

echo json_encode($result, JSON_PRETTY_PRINT);
