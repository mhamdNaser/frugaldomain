<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Catalog\Models\Collection;
use App\Modules\Catalog\Models\CollectionProduct;
use App\Modules\Catalog\Models\Product;
use App\Modules\Shopify\DTOs\CollectionData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use Illuminate\Support\Str;

class CollectionSyncService
{
    private const PAGE_SIZE = 50;

    /**
     * Sync collections for a single product (called from Job)
     */
    public function syncByProduct(Store $store, Product $product, string $shopifyProductId): void
    {
        $collections = $this->fetchCollections($store, $shopifyProductId);

        foreach ($collections as $index => $collection) {
            $data = $this->collectionData($collection);

            $model = $data ? $this->upsertCollection($store, $data) : null;

            if ($model) {
                $this->attachProductToCollection($store, $model, $product, $index + 1);
            }
        }
    }

    /**
     * Fetch collections from Shopify
     */
    private function fetchCollections(Store $store, string $shopifyProductId): array
    {
        $client = new ShopifyClient($store);
        $collections = [];
        $after = null;

        do {
            $response = $client->query(
                query: $this->getQuery(),
                variables: array_filter([
                    'id' => $shopifyProductId,
                    'first' => self::PAGE_SIZE,
                    'after' => $after,
                ]),
            );

            $connection = $response['data']['product']['collections'] ?? null;

            if (!is_array($connection)) {
                break;
            }

            $collections = array_merge($collections, $connection['edges'] ?? []);
            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $collections;
    }

    private function getQuery(): string
    {
        return <<<'GRAPHQL'
query GetProductCollections($id: ID!, $first: Int!, $after: String) {
  product(id: $id) {
    collections(first: $first, after: $after) {
      edges {
        cursor
        node {
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
      pageInfo {
        hasNextPage
        endCursor
      }
    }
  }
}
GRAPHQL;
    }

    private function upsertCollection(Store $store, CollectionData $data): ?Collection
    {
        return Collection::updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_collection_id' => $data->shopifyCollectionId,
            ],
            [
                'title' => $data->title,
                'handle' => $data->handle,
                'description' => $data->description,
                'image_url' => $data->imageUrl,
                'image_alt' => $data->imageAlt,
                'type' => $data->type,
                'seo_title' => $data->seoTitle,
                'seo_description' => $data->seoDescription,
                'is_active' => true,
            ]
        );
    }

    private function collectionData(array $collection): ?CollectionData
    {
        $node = $collection['node'] ?? null;

        if (!is_array($node) || empty($node['id']) || empty($node['title'])) {
            return null;
        }

        return new CollectionData(
            shopifyCollectionId: $node['id'],
            title: $node['title'],
            handle: $node['handle'] ?? Str::slug($node['title']),
            description: $node['description'] ?? null,
            imageUrl: $node['image']['url'] ?? null,
            imageAlt: $node['image']['altText'] ?? null,
            type: $this->resolveCollectionType($node),
            seoTitle: $node['seo']['title'] ?? null,
            seoDescription: $node['seo']['description'] ?? null,
            rawPayload: $node,
        );
    }

    private function attachProductToCollection(Store $store, Collection $collection, Product $product, int $position): void
    {
        CollectionProduct::updateOrCreate(
            [
                'collection_id' => $collection->id,
                'product_id' => $product->id,
            ],
            [
                'store_id' => $store->id,
                'position' => $position,
                'added_via' => 'auto',
            ]
        );
    }

    private function resolveCollectionType(array $node): string
    {
        return empty($node['ruleSet']) ? 'manual' : 'automated';
    }
}
