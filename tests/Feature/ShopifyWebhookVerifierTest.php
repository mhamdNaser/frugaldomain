<?php

use App\Modules\Shopify\Webhooks\Services\ShopifyWebhookVerifier;

it('verifies shopify webhook signature', function () {
    config(['shopify.webhook_secret' => 'test-secret']);

    $rawBody = '{"id":123,"title":"Example"}';
    $header = base64_encode(hash_hmac('sha256', $rawBody, 'test-secret', true));

    $verifier = new ShopifyWebhookVerifier();

    expect($verifier->verify($rawBody, $header))->toBeTrue();
    expect($verifier->verify($rawBody, 'invalid'))->toBeFalse();
});

