<?php

namespace App\Modules\Shopify\Webhooks\Handlers;

use App\Modules\Shopify\Webhooks\DTOs\WebhookData;

interface WebhookHandlerInterface
{
    public function handle(WebhookData $data): void;
}

