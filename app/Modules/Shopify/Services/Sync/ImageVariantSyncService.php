<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\CMS\Models\File;
use App\Modules\Shopify\DTOs\ImageData;
use App\Modules\Stores\Models\Store;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Shopify\Support\ShopifyHelper;

class ImageVariantSyncService
{
    public function syncByVariant(Store $store, ProductVariant $variant, string $shopifyVariantId): void
    {
        $image = $this->fetchImage($store, $shopifyVariantId);

        $imageData = $this->normalize($image);

        if (!$imageData) {
            return;
        }

        $this->syncImage($store, $variant, $imageData);
    }

    public function syncImage(Store $store, ProductVariant $variant, ImageData $image, int $position = 1): File
    {
        $file = File::query()
            ->where('fileable_type', ProductVariant::class)
            ->where('fileable_id', $variant->id)
            ->where('role', 'variant_image')
            ->where(function ($query) use ($image) {
                if ($image->shopifyImageId) {
                    $query->where('shopify_id', $image->shopifyImageId)
                        ->orWhere('url', $image->url);

                    return;
                }

                $query->where('url', $image->url);
            })
            ->firstOrNew([
                'fileable_type' => ProductVariant::class,
                'fileable_id' => $variant->id,
                'role' => 'variant_image',
            ]);

        $file->fill([
            'store_id' => $store->id,
            'disk' => 'public',
            'path' => $image->url,
            'url' => $image->url,
            'mime_type' => 'image/jpeg',
            'type' => 'image',
            'position' => $position,
            'fileable_type' => ProductVariant::class,
            'fileable_id' => $variant->id,
            'width' => $image->width,
            'height' => $image->height,
            'altText' => $image->alt,
            'meta' => [
                'alt' => $image->alt,
                'width' => $image->width,
                'height' => $image->height,
                'shopify_gid' => $image->shopifyImageId ? "gid://shopify/ProductImage/{$image->shopifyImageId}" : null,
            ],
            'shopify_id' => $image->shopifyImageId,
        ]);

        $file->save();

        return $file;
    }

    private function fetchImage(Store $store, string $variantId): ?array
    {
        $client = new ShopifyClient($store);

        $response = $client->query(
            query: $this->getQuery(),
            variables: ['id' => $variantId]
        );

        return $response['data']['node']['image'] ?? null;
    }

    private function getQuery(): string
    {
        return <<<'GRAPHQL'
query GetVariantImage($id: ID!) {
  node(id: $id) {
    ... on ProductVariant {
      image {
        id
        url
        altText
        width
        height
      }
    }
  }
}
GRAPHQL;
    }

    public function normalize(mixed $image): ?ImageData
    {
        if (!is_array($image) || empty($image['url'])) {
            return null;
        }

        return new ImageData(
            shopifyImageId: ShopifyHelper::extractId($image['id'] ?? null),
            url: $image['url'],
            alt: $image['altText'] ?? null,
            position: 0,
            width: $image['width'] ?? null,
            height: $image['height'] ?? null,
        );
    }
}
