<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductMedia;
use App\Modules\Shopify\DTOs\ProductAdvancedMediaData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use Illuminate\Support\Str;

class ProductAdvancedMediaSyncService
{
    private const PAGE_SIZE = 25;

    public function sync(Store $store): int
    {
        $client = new ShopifyClient($store);
        $after = null;
        $count = 0;

        do {
            $response = $client->query(
                query: $this->query(),
                variables: array_filter([
                    'first' => self::PAGE_SIZE,
                    'after' => $after,
                ])
            );

            $connection = $response['data']['products'] ?? null;
            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $productEdge) {
                $productNode = $productEdge['node'] ?? null;
                if (!is_array($productNode) || empty($productNode['id'])) {
                    continue;
                }

                $product = Product::query()
                    ->where('store_id', $store->id)
                    ->where('shopify_product_id', $productNode['id'])
                    ->first();

                if (!$product) {
                    continue;
                }

                foreach (($productNode['media']['edges'] ?? []) as $mediaEdge) {
                    $mediaNode = $mediaEdge['node'] ?? null;
                    if (!is_array($mediaNode) || empty($mediaNode['id'])) {
                        continue;
                    }

                    $data = $this->map($productNode['id'], $mediaNode);
                    $this->persist($store, $product->id, $data);
                    $count++;
                }
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $count;
    }

    private function persist(Store $store, int $productId, ProductAdvancedMediaData $data): void
    {
        ProductMedia::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_media_id' => $data->shopifyMediaId,
            ],
            [
                'product_id' => $productId,
                'shopify_product_id' => $data->shopifyProductId,
                'media_content_type' => $data->contentType,
                'status' => $data->status,
                'position' => $data->position,
                'alt' => $data->alt,
                'url' => $data->url,
                'preview_url' => $data->previewUrl,
                'mime_type' => $data->mimeType,
                'width' => $data->width,
                'height' => $data->height,
                'raw_payload' => $data->rawPayload,
            ]
        );
    }

    private function map(string $shopifyProductId, array $media): ProductAdvancedMediaData
    {
        $previewUrl = $media['preview']['image']['url'] ?? null;
        $url = $media['image']['url']
            ?? $media['sources'][0]['url']
            ?? $media['embedUrl']
            ?? $previewUrl;

        return new ProductAdvancedMediaData(
            shopifyProductId: $shopifyProductId,
            shopifyMediaId: (string) $media['id'],
            contentType: $media['mediaContentType'] ?? ($media['__typename'] ?? null),
            status: $media['status'] ?? $media['fileStatus'] ?? null,
            position: (int) ($media['position'] ?? 0),
            alt: isset($media['alt']) ? Str::limit(trim((string) $media['alt']), 255, '') : null,
            url: $url,
            previewUrl: $previewUrl,
            mimeType: $media['sources'][0]['mimeType'] ?? null,
            width: $media['image']['width'] ?? $media['preview']['image']['width'] ?? null,
            height: $media['image']['height'] ?? $media['preview']['image']['height'] ?? null,
            rawPayload: $media,
        );
    }

    private function query(): string
    {
        return <<<'GRAPHQL'
query SyncProductAdvancedMedia($first: Int!, $after: String) {
  products(first: $first, after: $after) {
    edges {
      node {
        id
        media(first: 100) {
          edges {
            node {
              __typename
              id
              alt
              mediaContentType
              status
              ... on MediaImage {
                image {
                  url
                  width
                  height
                }
              }
              ... on Video {
                sources {
                  url
                  mimeType
                }
              }
              ... on ExternalVideo {
                embedUrl
              }
              ... on Model3d {
                sources {
                  url
                  mimeType
                }
              }
              preview {
                image {
                  url
                  width
                  height
                }
              }
            }
          }
        }
      }
    }
    pageInfo {
      hasNextPage
      endCursor
    }
  }
}
GRAPHQL;
    }
}
