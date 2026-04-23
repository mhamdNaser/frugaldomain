<?php

namespace App\Modules\Shopify\Webhooks\Jobs;

use App\Modules\Shopify\DTOs\ProductData;
use App\Modules\Shopify\Exceptions\ShopifySyncException;
use App\Modules\Shopify\Jobs\SyncCollectionsJob;
use App\Modules\Shopify\Jobs\SyncInventoryJob;
use App\Modules\Shopify\Jobs\SyncMetafieldsJob;
use App\Modules\Shopify\Jobs\SyncProductImagesJob;
use App\Modules\Shopify\Jobs\SyncProductVariantsJob;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Shopify\Services\Sync\ProductSyncService;
use App\Modules\Stores\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncSingleProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public readonly string $storeId,
        public readonly string $shopifyProductId,
        public readonly ?string $webhookExternalId = null,
    ) {
        $this->onQueue('shopify-sync');
    }

    /**
     * @throws ShopifySyncException
     */
    public function handle(ProductSyncService $service): void
    {
        $store = Store::query()->findOrFail($this->storeId);

        $gid = $this->toProductGid($this->shopifyProductId);

        $client = new ShopifyClient($store);
        $response = $client->query($this->query(), ['id' => $gid]);

        $node = $response['data']['product'] ?? null;

        if (!is_array($node) || empty($node['id'])) {
            throw new ShopifySyncException('Invalid Shopify product response structure.');
        }

        $dto = new ProductData(
            shopifyProductId: $node['id'],
            title: $node['title'] ?? '',
            slug: $node['handle'] ?? '',
            description: $node['description'] ?? null,
            handle: $node['handle'] ?? '',
            status: $node['status'] ?? 'draft',
            seoTitle: $node['seo']['title'] ?? null,
            seoDescription: $node['seo']['description'] ?? null,
            publishedAt: $node['publishedAt'] ?? null,
            shopifyCreatedAt: $node['createdAt'] ?? null,
            shopifyUpdatedAt: $node['updatedAt'] ?? null,
            vendor: $node['vendor'] ?? null,
            productType: $node['productType'] ?? null,
            categoryId: $node['category']['id'] ?? null,
            categoryName: $node['category']['name'] ?? null,
            isGiftCard: $node['isGiftCard'] ?? null,
            hasOnlyDefaultVariant: $node['hasOnlyDefaultVariant'] ?? null,
            rawPayload: $node,
            featuredImage: $node['featuredImage'] ?? null,
            tags: $node['tags'] ?? [],
            metafields: [],
            options: $node['options'] ?? [],
            images: [],
            variants: [],
            collections: [],
        );

        $product = $service->syncOne($store, $dto);

        SyncProductVariantsJob::dispatch($store->id, $product->id, $dto->shopifyProductId);
        SyncProductImagesJob::dispatch($store->id, $product->id, $dto->shopifyProductId);
        SyncInventoryJob::dispatch($store->id, $product->id, $dto->shopifyProductId);
        SyncMetafieldsJob::dispatch($store->id, $product->id, $dto->shopifyProductId)->onQueue('shopify-metafields');
        SyncCollectionsJob::dispatch($store->id, $product->id, $dto->shopifyProductId)->onQueue('shopify-collections');
    }

    private function toProductGid(string $id): string
    {
        if (str_starts_with($id, 'gid://')) {
            return $id;
        }

        return 'gid://shopify/Product/' . trim($id);
    }

    private function query(): string
    {
        return <<<'GRAPHQL'
query GetProduct($id: ID!) {
  product(id: $id) {
    id
    title
    description
    handle
    vendor
    productType
    status
    tags
    isGiftCard
    hasOnlyDefaultVariant
    publishedAt
    createdAt
    updatedAt

    featuredImage {
      id
      url
      altText
    }

    seo {
      title
      description
    }

    category {
      id
      name
    }

    options {
      id
      name
      position
      optionValues {
        id
        name
        swatch {
          color
          image {
            image {
              url
            }
          }
        }
      }
    }
  }
}
GRAPHQL;
    }
}

