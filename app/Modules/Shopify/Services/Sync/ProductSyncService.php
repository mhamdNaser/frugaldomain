<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Catalog\Models\Product;
use App\Modules\Shopify\DTOs\ProductData;
use App\Modules\Stores\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSyncService
{
    public function __construct(
        private readonly VendorSyncService $vendorSyncService,
        private readonly ProductTypeSyncService $productTypeSyncService,
        private readonly CategorySyncService $categorySyncService,
        private readonly TagSyncService $tagSyncService,
        private readonly OptionSyncService $optionSyncService
    ) {}

    /**
     * @param array<int, ProductData> $products
     * @return array<string, mixed>
     */
    public function syncMany(Store $store, array $products): array
    {
        $results = [];

        foreach ($products as $productData) {
            if (!$productData instanceof ProductData) {
                continue;
            }

            $results[] = $this->syncOne($store, $productData);
        }

        return [
            'synced_count' => count($results),
            'products' => $results,
        ];
    }


    public function syncOne(Store $store, ProductData $productData): Product
    {
        return DB::transaction(function () use ($store, $productData) {

            $vendorId = $this->vendorSyncService->sync($store, $productData->vendor);
            $categoryId = $this->categorySyncService->sync($store, $productData->categoryId, $productData->categoryName);
            $productTypeId = $this->productTypeSyncService->sync($store, $productData->productType);

            $product = $this->upsertProduct($store, $productData, $vendorId, $categoryId, $productTypeId);
            $optionValueMap = $this->optionSyncService->sync($store, $product, $productData->options);


            if (!empty($productData->tags)) {
                $this->tagSyncService->sync(
                    store: $store,
                    taggableModel: $product,
                    tags: $productData->tags,
                    replaceOldTags: true
                );
            }
            
            return $product;
        });
    }

    private function upsertProduct(Store $store, ProductData $productData, ?int $vendorId, ?int $categoryId, ?int $productTypeId): Product
    {
        $attributes = [
            'store_id' => $store->id,
            'shopify_product_id' => $productData->shopifyProductId,
        ];

        $values = array_merge(
            [
                'vendor_id' => $vendorId,
                'product_type_id' => $productTypeId,
                'category_id' => $categoryId,
                'title' => $productData->title,
                'description' => $productData->description,
                'handle' => $productData->handle,
                'seo_title' => $productData->seoTitle,
                'seo_description' => $productData->seoDescription,
                'slug' => Str::slug($productData->title),
                'status' => $productData->status,
                'tags' => $productData->tags,
                'featured_image' => $productData->featuredImage,
                'published_at' => $productData->publishedAt,
                'shopify_created_at' => $productData->shopifyCreatedAt,
                'shopify_updated_at' => $productData->shopifyUpdatedAt,
                'raw_payload' => $productData->rawPayload,
            ]
        );

        $product = Product::query()->updateOrCreate($attributes, $values);

        return $product;
    }
}
