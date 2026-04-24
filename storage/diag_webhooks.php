<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
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
    ],
    'webhook_logs' => DB::table('webhook_logs')
        ->where('store_id', $store->id)
        ->orderByDesc('id')
        ->limit(15)
        ->get(['id','topic','status','attempts','error_message','received_at','processed_at']),
    'latest_jobs' => DB::table('jobs')
        ->orderByDesc('id')
        ->limit(20)
        ->get(['id','queue','attempts','created_at','available_at']),
    'latest_failed_jobs' => DB::table('failed_jobs')
        ->orderByDesc('id')
        ->limit(20)
        ->get(['id','queue','failed_at']),
    'latest_sync_runs' => DB::table('sync_runs')
        ->where('store_id', $store->id)
        ->orderByDesc('id')
        ->limit(20)
        ->get(['id','type','status','fetched_count','synced_count','failed_count','error_message','created_at','updated_at']),
    'latest_sync_errors' => DB::table('sync_errors')
        ->where('store_id', $store->id)
        ->orderByDesc('id')
        ->limit(20)
        ->get(['id','type','message','created_at']),
];

echo json_encode($result, JSON_PRETTY_PRINT);
