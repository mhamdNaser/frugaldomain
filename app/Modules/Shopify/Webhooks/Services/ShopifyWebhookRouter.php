<?php

namespace App\Modules\Shopify\Webhooks\Services;

use App\Modules\Shopify\Webhooks\Handlers\CollectionPublishWebhookHandler;
use App\Modules\Shopify\Webhooks\Handlers\CollectionUnpublishWebhookHandler;
use App\Modules\Shopify\Webhooks\Handlers\GenericSyncWebhookHandler;
use App\Modules\Shopify\Webhooks\Handlers\InventoryUpdateWebhookHandler;
use App\Modules\Shopify\Webhooks\Handlers\ProductCreateWebhookHandler;
use App\Modules\Shopify\Webhooks\Handlers\ProductDeleteWebhookHandler;
use App\Modules\Shopify\Webhooks\Handlers\ProductUpdateWebhookHandler;
use App\Modules\Shopify\Webhooks\Handlers\WebhookHandlerInterface;

class ShopifyWebhookRouter
{
    public function __construct(
        private readonly ShopifyWebhookTopicSyncResolver $syncResolver,
    ) {}

    public function resolve(string $topic): ?WebhookHandlerInterface
    {
        $topic = strtolower(trim($topic));

        $directHandler = match ($topic) {
            'products/create' => app(ProductCreateWebhookHandler::class),
            'products/update' => app(ProductUpdateWebhookHandler::class),
            'products/delete' => app(ProductDeleteWebhookHandler::class),

            'collections/create',
            'collections/update',
            'collection_listings/add',
            'collection_listings/update' => app(CollectionPublishWebhookHandler::class),

            'collections/delete',
            'collection_listings/remove' => app(CollectionUnpublishWebhookHandler::class),

            'inventory_levels/update' => app(InventoryUpdateWebhookHandler::class),

            default => null,
        };

        if ($directHandler) {
            return $directHandler;
        }

        if ($this->syncResolver->resolve($topic) !== []) {
            return app(GenericSyncWebhookHandler::class);
        }

        return null;
    }
}
