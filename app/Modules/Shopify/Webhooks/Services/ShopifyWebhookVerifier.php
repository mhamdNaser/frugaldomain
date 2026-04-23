<?php

namespace App\Modules\Shopify\Webhooks\Services;

class ShopifyWebhookVerifier
{
    public function verify(string $rawBody, ?string $hmacHeader): bool
    {
        $secret = (string) config('shopify.webhook_secret');

        if ($secret === '' || !$hmacHeader) {
            return false;
        }

        $calculated = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        return hash_equals($calculated, (string) $hmacHeader);
    }
}

