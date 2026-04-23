<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Shopify\DTOs\ImageData;
use App\Modules\Catalog\Models\Product;
use App\Modules\CMS\Models\File;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Shopify\Support\ShopifyHelper;
use App\Modules\Stores\Models\Store;

class ImageProductSyncService
{
    private const PAGE_SIZE = 100;

    /**
     * Sync images via Shopify API (called from Job)
     */
    public function syncByProduct(Store $store, Product $product, string $shopifyProductId): void
    {
        $images = $this->fetchImages($store, $shopifyProductId);

        $this->syncImages($store, $product, $images);
    }

    /**
     * Fetch images from Shopify
     */
    private function fetchImages(Store $store, string $shopifyProductId): array
    {
        $client = new ShopifyClient($store);
        $images = [];
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

            $connection = $response['data']['product']['images'] ?? null;

            if (!is_array($connection)) {
                break;
            }

            $images = array_merge($images, $connection['edges'] ?? []);
            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $images;
    }

    private function getQuery(): string
    {
        return <<<'GRAPHQL'
query GetProductImages($id: ID!, $first: Int!, $after: String) {
  product(id: $id) {
    images(first: $first, after: $after) {
      edges {
        cursor
        node {
          id
          url
          altText
          width
          height
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

    /**
     * Core sync logic (نفس القديم)
     */
    private function syncImages(Store $store, Product $product, array $images): void
    {
        foreach ($images as $index => $image) {

            $image = $this->normalize($image['node'] ?? null);

            if (!$image) {
                continue;
            }

            $this->upsertFile($store, $product, $image, 'product_image', $index + 1);
        }
    }

    private function normalize(mixed $image): ?ImageData
    {
        if (!$image || empty($image['url'])) {
            return null;
        }

        return new ImageData(
            shopifyImageId: ShopifyHelper::extractId($image['id'] ?? null),
            url: $image['url'] ?? null,
            alt: $image['altText'] ?? null,
            position: 0,
            width: $image['width'] ?? null,
            height: $image['height'] ?? null,
        );
    }

    private function upsertFile(Store $store, $model, ImageData $data, string $role, int $position): File
    {
        $file = File::query()
            ->where('fileable_type', get_class($model))
            ->where('fileable_id', $model->id)
            ->where('role', $role)
            ->where(function ($query) use ($data) {
                if ($data->shopifyImageId) {
                    $query->where('shopify_id', $data->shopifyImageId)
                        ->orWhere('url', $data->url);

                    return;
                }

                $query->where('url', $data->url);
            })
            ->firstOrNew([
                'fileable_type' => get_class($model),
                'fileable_id' => $model->id,
                'role' => $role,
            ]);

        $file->fill([
            'store_id' => $store->id,
            'disk' => 'public',
            'path' => $data->url,
            'url' => $data->url,
            'mime_type' => 'image/jpeg',
            'type' => 'image',
            'position' => $position,
            'fileable_type' => get_class($model),
            'fileable_id' => $model->id,
            'width' => $data->width,
            'height' => $data->height,
            'altText' => $data->alt,
            'meta' => [
                'alt' => $data->alt,
                'width' => $data->width,
                'height' => $data->height,
                'shopify_gid' => $data->shopifyImageId ? "gid://shopify/ProductImage/{$data->shopifyImageId}" : null,
            ],
            'shopify_id' => $data->shopifyImageId,
        ]);

        $file->save();

        return $file;
    }
}
