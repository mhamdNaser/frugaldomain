<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Shopify\DTOs\InventoryData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;

class ProductInventorySyncService
{
    private const PAGE_SIZE = 100;

    public function __construct(
        private readonly InventorySyncService $inventorySync,
    ) {}

    public function syncByProduct(Store $store, Product $product, string $shopifyProductId): void
    {
        $variants = $this->fetchVariants($store, $shopifyProductId);

        foreach ($variants as $edge) {
            $node = $edge['node'] ?? null;

            if (!is_array($node) || empty($node['id'])) {
                continue;
            }

            $variant = ProductVariant::query()
                ->where('store_id', $store->id)
                ->where('product_id', $product->id)
                ->where('shopify_variant_id', $node['id'])
                ->first();

            if (!$variant) {
                continue;
            }

            $this->inventorySync->syncVariantInventory(
                store: $store,
                variant: $variant,
                inventoryData: $this->mapInventory($node),
                referenceType: 'shopify_inventory_sync',
                referenceId: null,
            );
        }
    }

    private function fetchVariants(Store $store, string $shopifyProductId): array
    {
        $client = new ShopifyClient($store);
        $variants = [];
        $after = null;

        do {
            $response = $client->query(
                query: $this->query(),
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

    private function mapInventory(array $variantNode): InventoryData
    {
        $inventoryItem = $variantNode['inventoryItem'] ?? [];
        $locations = [];

        foreach ($inventoryItem['inventoryLevels']['edges'] ?? [] as $edge) {
            $level = $edge['node'] ?? [];
            $location = $level['location'] ?? [];

            $locations[] = [
                'id' => $location['id'] ?? null,
                'name' => $location['name'] ?? null,
                'quantity' => $this->availableQuantity($level['quantities'] ?? []),
                'updated_at' => $this->availableUpdatedAt($level['quantities'] ?? []),
                'raw_payload' => $level,
            ];
        }

        return new InventoryData(
            inventoryItemId: $inventoryItem['id'] ?? '',
            tracked: $inventoryItem['tracked'] ?? false,
            requiresShipping: $inventoryItem['requiresShipping'] ?? false,
            weight: $inventoryItem['measurement']['weight']['value'] ?? null,
            weightUnit: $inventoryItem['measurement']['weight']['unit'] ?? 'KILOGRAMS',
            locations: $locations,
            quantity: (int) ($variantNode['inventoryQuantity'] ?? 0),
            rawPayload: $inventoryItem,
        );
    }

    private function availableQuantity(array $quantities): int
    {
        foreach ($quantities as $quantity) {
            if (($quantity['name'] ?? null) === 'available') {
                return (int) ($quantity['quantity'] ?? 0);
            }
        }

        return 0;
    }

    private function availableUpdatedAt(array $quantities): ?string
    {
        foreach ($quantities as $quantity) {
            if (($quantity['name'] ?? null) === 'available') {
                return $quantity['updatedAt'] ?? null;
            }
        }

        return null;
    }

    private function query(): string
    {
        return <<<'GRAPHQL'
query GetProductInventory($id: ID!, $first: Int!, $after: String) {
  product(id: $id) {
    variants(first: $first, after: $after) {
      edges {
        node {
          id
          inventoryQuantity
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
            inventoryLevels(first: 50) {
              edges {
                node {
                  id
                  quantities(names: ["available"]) {
                    name
                    quantity
                    updatedAt
                  }
                  location {
                    id
                    name
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
}
GRAPHQL;
    }
}
