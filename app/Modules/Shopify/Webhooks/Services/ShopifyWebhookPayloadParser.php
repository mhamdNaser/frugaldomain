<?php

namespace App\Modules\Shopify\Webhooks\Services;

use App\Modules\Shopify\Webhooks\DTOs\WebhookData;
use App\Modules\Stores\Models\Store;
use Illuminate\Http\Request;

class ShopifyWebhookPayloadParser
{
    public function parse(Request $request, string $rawBody): WebhookData
    {
        $topic = (string) $request->header('X-Shopify-Topic', '');
        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        $webhookId = $request->header('X-Shopify-Webhook-Id');
        $hmac = $request->header('X-Shopify-Hmac-Sha256');

        $normalizedDomain = $shopDomain ? $this->normalizeDomain((string) $shopDomain) : null;

        $storeId = null;

        if ($normalizedDomain) {
            $store = Store::query()
                ->whereRaw('LOWER(shopify_domain) = ?', [mb_strtolower($normalizedDomain)])
                ->first();

            $storeId = $store?->id;
        }

        $payload = json_decode($rawBody, true);

        return new WebhookData(
            storeId: $storeId,
            provider: 'shopify',
            topic: strtolower(trim($topic)),
            externalId: $webhookId ? (string) $webhookId : null,
            shopDomain: $normalizedDomain,
            payload: is_array($payload) ? $payload : null,
            rawBody: $rawBody,
            headers: $request->headers->all(),
            hmacHeader: $hmac ? (string) $hmac : null,
        );
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);
        $domain = preg_replace('#^https?://#', '', $domain);

        return rtrim((string) $domain, '/');
    }
}

