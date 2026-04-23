<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\InventoryLevel;
use App\Modules\Shopify\DTOs\InventoryStateData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;

class InventoryStatesSyncService
{
    private const PRODUCT_PAGE_SIZE = 10;
    private const VARIANT_PAGE_SIZE = 25;
    private const LEVEL_PAGE_SIZE = 25;

    public function sync(Store $store): int
    {
        $client = new ShopifyClient($store);
        $count = 0;
        $after = null;

        do {
            $response = $client->query(
                query: $this->query(),
                variables: array_filter([
                    'first' => self::PRODUCT_PAGE_SIZE,
                    'after' => $after,
                    'variantFirst' => self::VARIANT_PAGE_SIZE,
                    'levelFirst' => self::LEVEL_PAGE_SIZE,
                ]),
            );

            $connection = $response['data']['products'] ?? null;
            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $productEdge) {
                $product = $productEdge['node'] ?? null;
                if (!is_array($product)) {
                    continue;
                }

                foreach (($product['variants']['edges'] ?? []) as $variantEdge) {
                    $variantNode = $variantEdge['node'] ?? null;
                    if (!is_array($variantNode)) {
                        continue;
                    }

                    $variantId = ProductVariant::query()
                        ->where('store_id', $store->id)
                        ->where('shopify_variant_id', $variantNode['id'] ?? null)
                        ->value('id');

                    if (!$variantId) {
                        continue;
                    }

                    foreach (($variantNode['inventoryItem']['inventoryLevels']['edges'] ?? []) as $levelEdge) {
                        $levelNode = $levelEdge['node'] ?? null;
                        if (!is_array($levelNode)) {
                            continue;
                        }

                        $data = $this->mapLevel($levelNode);
                        $this->persist($store, $variantId, $data);
                        $count++;
                    }
                }
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $count;
    }

    private function persist(Store $store, int $variantId, InventoryStateData $data): void
    {
        InventoryLevel::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'inventory_item_id' => $data->inventoryItemId,
                'shopify_location_id' => $data->shopifyLocationId,
            ],
            [
                'product_variant_id' => $variantId,
                'available' => $data->available,
                'committed' => $data->committed,
                'incoming' => $data->incoming,
                'reserved' => $data->reserved,
                'on_hand' => $data->onHand,
                'shopify_updated_at' => $data->updatedAt,
                'raw_payload' => $data->rawPayload,
            ]
        );
    }

    private function mapLevel(array $levelNode): InventoryStateData
    {
        $quantities = collect($levelNode['quantities'] ?? []);
        $inventoryItemId = $levelNode['item']['id'] ?? '';

        return new InventoryStateData(
            inventoryItemId: (string) $inventoryItemId,
            shopifyLocationId: $levelNode['location']['id'] ?? null,
            available: (int) ($quantities->firstWhere('name', 'available')['quantity'] ?? 0),
            committed: (int) ($quantities->firstWhere('name', 'committed')['quantity'] ?? 0),
            incoming: (int) ($quantities->firstWhere('name', 'incoming')['quantity'] ?? 0),
            reserved: (int) ($quantities->firstWhere('name', 'reserved')['quantity'] ?? 0),
            onHand: (int) ($quantities->firstWhere('name', 'on_hand')['quantity'] ?? 0),
            updatedAt: $quantities->firstWhere('name', 'available')['updatedAt'] ?? null,
            rawPayload: $levelNode,
        );
    }

    private function query(): string
    {
        return <<<'GRAPHQL'
query SyncInventoryStates($first: Int!, $after: String, $variantFirst: Int!, $levelFirst: Int!) {
  products(first: $first, after: $after) {
    edges {
      node {
        id
        variants(first: $variantFirst) {
          edges {
            node {
              id
              inventoryItem {
                id
                inventoryLevels(first: $levelFirst) {
                  edges {
                    node {
                      location {
                        id
                      }
                      item {
                        id
                      }
                      quantities(names: ["available", "committed", "incoming", "reserved", "on_hand"]) {
                        name
                        quantity
                        updatedAt
                      }
                    }
                  }
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
