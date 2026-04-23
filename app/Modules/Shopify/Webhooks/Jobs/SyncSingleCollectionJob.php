<?php

namespace App\Modules\Shopify\Webhooks\Jobs;

use App\Modules\Catalog\Models\Collection;
use App\Modules\Shopify\Exceptions\ShopifySyncException;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SyncSingleCollectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(
        public readonly string $storeId,
        public readonly string $shopifyCollectionId,
        public readonly ?string $webhookExternalId = null,
    ) {
        $this->onQueue('shopify-sync');
    }

    /**
     * @throws ShopifySyncException
     */
    public function handle(): void
    {
        $store = Store::query()->findOrFail($this->storeId);

        $gid = $this->toCollectionGid($this->shopifyCollectionId);

        $client = new ShopifyClient($store);
        $response = $client->query($this->query(), ['id' => $gid]);

        $node = $response['data']['node'] ?? null;

        if (!is_array($node) || empty($node['id']) || empty($node['title'])) {
            throw new ShopifySyncException('Invalid Shopify collection response structure.');
        }

        Collection::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_collection_id' => $node['id'],
            ],
            [
                'title' => $node['title'],
                'handle' => $node['handle'] ?? Str::slug($node['title']),
                'description' => $node['description'] ?? null,
                'image_url' => $node['image']['url'] ?? null,
                'image_alt' => $node['image']['altText'] ?? null,
                'type' => empty($node['ruleSet']) ? 'manual' : 'automated',
                'seo_title' => $node['seo']['title'] ?? null,
                'seo_description' => $node['seo']['description'] ?? null,
                'is_active' => true,
            ]
        );
    }

    private function toCollectionGid(string $id): string
    {
        if (str_starts_with($id, 'gid://')) {
            return $id;
        }

        return 'gid://shopify/Collection/' . trim($id);
    }

    private function query(): string
    {
        return <<<'GRAPHQL'
query GetCollection($id: ID!) {
  node(id: $id) {
    ... on Collection {
      id
      title
      handle
      description
      image {
        url
        altText
      }
      ruleSet {
        appliedDisjunctively
      }
      seo {
        title
        description
      }
    }
  }
}
GRAPHQL;
    }
}

