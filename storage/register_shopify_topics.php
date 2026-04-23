<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$service = $app->make(App\Modules\Shopify\Webhooks\Services\ShopifyWebhookTopicRegistrar::class);
$result = $service->registerForAllStores();
echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
