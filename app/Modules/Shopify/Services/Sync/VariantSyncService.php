<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Shopify\DTOs\ImageData;
use App\Modules\Shopify\DTOs\InventoryData;
use App\Modules\Shopify\DTOs\ProductVariantData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Shopify\Support\ShopifyHelper;
use App\Modules\Stores\Models\Store;

class VariantSyncService
{
    private const PAGE_SIZE = 100;

    public function __construct(
        private readonly ImageVariantSyncService $imageSync
    ) {}

    public function syncByProduct(Store $store, Product $product, string $shopifyProductId): void
    {
        $variants = $this->fetchVariants($store, $shopifyProductId);

        // 🔥 نجيب mapping من DB
        $optionValueMap = $this->buildOptionValueMap($product);

        foreach ($variants as $variantNode) {

            $data = $this->mapVariant($variantNode['node']);

            $variantData = new ProductVariantData(
                shopifyVariantId: $data['shopifyVariantId'],
                title: $data['title'] ?? null,
                sku: $data['sku'] ?? null,
                barcode: $data['barcode'] ?? null,
                price: $data['price'] ?? null,
                compareAtPrice: $data['compareAtPrice'] ?? null,
                isDefault: $data['isDefault'] ?? false,
                availableForSale: $data['availableForSale'] ?? false,
                taxable: $data['taxable'] ?? false,
                position: $data['position'] ?? null,
                inventoryQuantity: $data['inventoryQuantity'] ?? 0,
                shopifyCreatedAt: $data['shopifyCreatedAt'] ?? null,
                shopifyUpdatedAt: $data['shopifyUpdatedAt'] ?? null,
                rawPayload: $data['rawPayload'] ?? null,
                image: $data['image'] ?? null,
                inventory: $data['inventory'] ?? null,
                selectedOptions: $data['selectedOptions'] ?? [],
            );

            $variantModel = $this->upsertVariant($store, $product, $variantData);

            // options
            $this->syncOptions($variantModel, $variantData, $optionValueMap);

            // 🔥🔥🔥 NEW (مهم جداً)
            if ($variantData->image) {
                $this->imageSync->syncImage(
                    store: $store,
                    variant: $variantModel,
                    image: $variantData->image,
                    position: 1
                );
            }
        }

        $this->syncProductPriceRange($product);
    }

    private function fetchVariants(Store $store, string $shopifyProductId): array
    {
        $client = new ShopifyClient($store);
        $variants = [];
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

            $connection = $response['data']['product']['variants'] ?? null;

            if (!is_array($connection)) {
                break;
            }

            $variants = array_merge($variants, $connection['edges'] ?? []);
            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $variants;
    }

    private function mapVariant(array $node): array
    {
        return [
            'shopifyVariantId' => $node['id'],
            'title' => $node['title'] ?? null,
            'sku' => $node['sku'] ?? null,
            'barcode' => $node['barcode'] ?? null,
            'price' => isset($node['price']) ? (float) $node['price'] : null,
            'compareAtPrice' => isset($node['compareAtPrice']) ? (float) $node['compareAtPrice'] : null,

            'isDefault' => false, // أو logic حسبك
            'availableForSale' => $node['availableForSale'] ?? false,
            'taxable' => $node['taxable'] ?? false,

            'position' => $node['position'] ?? null,
            'inventoryQuantity' => $node['inventoryQuantity'] ?? 0,

            'shopifyCreatedAt' => $node['createdAt'] ?? null,
            'shopifyUpdatedAt' => $node['updatedAt'] ?? null,

            'rawPayload' => $node,

            'image' => $this->mapImage($node['image'] ?? null),
            'inventory' => $this->mapInventory($node['inventoryItem'] ?? null, $node),

            // إذا بدك لاحقًا:
            'selectedOptions' => $node['selectedOptions'] ?? [],
        ];
    }

    private function getQuery(): string
    {
        return <<<'GRAPHQL'
query GetProductVariants($id: ID!, $first: Int!, $after: String) {
  product(id: $id) {
    variants(first: $first, after: $after) {
      edges {
        cursor
        node {
          id
          title
          sku
          barcode
          price
          compareAtPrice
          position
          availableForSale
          taxable
          inventoryQuantity
          createdAt
          updatedAt

          selectedOptions {
            name
            value
            optionValue {
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

          image {
            id
            url
            altText
            width
            height
          }

          inventoryItem {
            id
            tracked
            requiresShipping
            measurement {
              weight {
                value
                unit
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
}
GRAPHQL;
    }

    private function mapImage(?array $image): ?ImageData
    {
        if (!$image || empty($image['url'])) {
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

    private function mapInventory(?array $inventoryItem, array $node): InventoryData
    {
        return new InventoryData(
            inventoryItemId: $inventoryItem['id'] ?? '',
            tracked: $inventoryItem['tracked'] ?? false,
            requiresShipping: $inventoryItem['requiresShipping'] ?? false,
            weight: $inventoryItem['measurement']['weight']['value'] ?? null,
            weightUnit: $inventoryItem['measurement']['weight']['unit'] ?? 'KILOGRAMS',
            locations: [],
            quantity: (int) ($node['inventoryQuantity'] ?? 0),
            rawPayload: $inventoryItem,
        );
    }

    private function buildOptionValueMap(Product $product): array
    {
        $product->load('options.values');

        $map = [];

        foreach ($product->options as $option) {
            foreach ($option->values as $value) {
                $map[$option->name]['by_value'][$value->value] = $value->id;
                $map[$option->name]['by_label'][$value->label] = $value->id;
            }
        }

        return $map;
    }

    private function upsertVariant(
        Store $store,
        Product $product,
        ProductVariantData $variantData
    ): ProductVariant {
        $attributes = [
            'store_id' => $store->id,
            'shopify_variant_id' => $variantData->shopifyVariantId,
        ];

        $values = array_merge(
            [
                'store_id' => $store->id,
                'product_id' => $product->id,
            ],
            [
                'shopify_variant_id' => $variantData->shopifyVariantId,
                'title' => $variantData->title,
                'sku' => $variantData->sku,
                'barcode' => $variantData->barcode,
                'price' => $variantData->price,
                'compare_at_price' => $variantData->compareAtPrice,
                'is_default' => $variantData->isDefault,
                'availableForSale' => $variantData->availableForSale,
                'taxable' => $variantData->taxable,
                'position' => $variantData->position,
                'inventory_quantity' => $variantData->inventoryQuantity,
                'shopify_created_at' => $variantData->shopifyCreatedAt,
                'shopify_updated_at' => $variantData->shopifyUpdatedAt,
                'raw_payload' => $variantData->rawPayload,
            ]
        );

        /** @var ProductVariant $variant */
        $variant = ProductVariant::query()->updateOrCreate($attributes, $values);

        return $variant;
    }

    private function syncOptions(
        ProductVariant $variant,
        ProductVariantData $variantData,
        array $optionValueMap
    ): void {
        $options = $variantData->selectedOptions
            ?? ($variantData->rawPayload['selectedOptions'] ?? []);

        $optionValueIds = [];

        foreach ($options as $option) {
            $name = $option['name'] ?? null;
            $label = $option['optionValue']['name'] ?? ($option['value'] ?? null);
            $swatchValue = $option['optionValue']['swatch']['color']
                ?? $option['optionValue']['swatch']['image']['image']['url']
                ?? null;

            if (!$name || !$label) {
                continue;
            }

            $optionId =
                ($swatchValue ? ($optionValueMap[$name]['by_value'][$swatchValue] ?? null) : null)
                ?? $optionValueMap[$name]['by_label'][$label]
                ?? $optionValueMap[$name]['by_value'][$label]
                ?? null;

            if ($optionId) {
                $optionValueIds[] = $optionId;
            }
        }

        if (empty($optionValueIds)) {
            return;
        }

        $variant->optionValues()->sync($optionValueIds);
    }

    private function syncProductPriceRange(Product $product): void
    {
        $query = ProductVariant::query()
            ->where('product_id', $product->id)
            ->whereNotNull('price');

        $minPrice = $query->min('price');
        $maxPrice = $query->max('price');

        $product->update([
            'price_min' => $minPrice,
            'price_max' => $maxPrice,
        ]);
    }
}
