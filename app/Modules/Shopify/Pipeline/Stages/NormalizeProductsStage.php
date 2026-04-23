<?php

namespace App\Modules\Shopify\Pipeline\Stages;

use App\Modules\Shopify\DTOs\ProductData;

class NormalizeProductsStage
{
    /**
     * تحويل raw Shopify edges إلى ProductData DTOs
     *
     * @param array $payload
     * @return array<int, ProductData>
     */
    public function handle(array $payload): array
    {
        $edges = $payload['edges'] ?? [];

        $products = [];

        foreach ($edges as $edge) {
            $node = $edge['node'] ?? null;

            if (!is_array($node)) {
                continue;
            }

            $products[] = new ProductData(
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

                metafields: $this->mapMetafields($node),

                options: $node['options'] ?? [],

                images: $this->mapImages($node),

                variants: $this->mapVariants($node),

                collections: $this->mapCollections($node),
            );
        }

        return $products;
    }

    private function mapImages(array $node): array
    {
        return $node['images']['edges'] ?? [];
    }

    private function mapVariants(array $node): array
    {
        return $node['variants']['edges'] ?? [];
    }

    private function mapCollections(array $node): array
    {
        return $node['collections']['edges'] ?? [];
    }

    private function mapMetafields(array $node): array
    {
        return $node['metafields']['edges'] ?? [];
    }
}
