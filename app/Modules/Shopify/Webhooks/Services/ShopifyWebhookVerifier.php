<?php

namespace App\Modules\Shopify\Webhooks\Services;

class ShopifyWebhookVerifier
{
    public function __construct(
        private readonly ShopifyWebhookSecretResolver $secretResolver,
    ) {}

    public function verify(string $rawBody, ?string $hmacHeader, ?string $storeId = null): bool
    {
        if (!$hmacHeader) {
            return false;
        }

        $secret = $this->secretResolver->resolveByStoreId($storeId);

        if (!$secret) {
            return false;
        }

        $calculated = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        return hash_equals($calculated, (string) $hmacHeader);
    }
}
