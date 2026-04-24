<?php

namespace App\Modules\Shopify\Webhooks\Services;

use App\Modules\Stores\Models\Store;

class ShopifyWebhookSecretResolver
{
    public function resolveByStoreId(?string $storeId): ?string
    {
        if ($storeId) {
            $store = Store::query()
                ->select(['id', 'shopify_webhook_secret'])
                ->find($storeId);

            $secret = trim((string) ($store?->shopify_webhook_secret ?? ''));
            if ($secret !== '') {
                return $secret;
            }
        }

        $fallback = (string) env('SHOPIFY_WEBHOOK_SECRET', '');
        $fallback = trim($fallback);

        return $fallback !== '' ? $fallback : null;
    }
}
