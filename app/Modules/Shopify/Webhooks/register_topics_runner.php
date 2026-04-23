<?php
require __DIR__ . '/../../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$service = $app->make(App\Modules\Shopify\Webhooks\Services\ShopifyWebhookTopicRegistrar::class);

$ownerId = $argv[1] ?? null;
$mode = $argv[2] ?? null;

if ($ownerId === '--all' || $mode === '--all') {
    $result = $service->registerForAllStores();
} elseif ($ownerId) {
    $result = $service->registerForOwnerId((string) $ownerId);
} else {
    $result = [
        'message' => 'Pass owner_id as first argument to run in multi-tenant mode.',
        'example' => 'php app/Modules/Shopify/Webhooks/register_topics_runner.php <owner_id>',
        'fallback_all' => 'php app/Modules/Shopify/Webhooks/register_topics_runner.php "" --all',
    ];
}

echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
