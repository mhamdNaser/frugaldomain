<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Catalog\Models\Market;
use App\Modules\Catalog\Models\PriceList;
use App\Modules\Catalog\Models\PriceListItem;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use Throwable;

class MarketsPriceListsSyncService
{
    private const PAGE_SIZE = 50;

    public function sync(Store $store): int
    {
        $client = new ShopifyClient($store);
        $count = 0;
        $after = null;
        $syncedPriceListIds = [];

        do {
            $response = $client->query(
                query: $this->query(),
                variables: array_filter([
                    'first' => self::PAGE_SIZE,
                    'after' => $after,
                ])
            );

            $connection = $response['data']['markets'] ?? null;
            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $marketEdge) {
                $marketNode = $marketEdge['node'] ?? null;
                if (!is_array($marketNode) || empty($marketNode['id'])) {
                    continue;
                }

                $market = Market::query()->updateOrCreate(
                    [
                        'store_id' => $store->id,
                        'shopify_market_id' => $marketNode['id'],
                    ],
                    [
                        'name' => $marketNode['name'] ?? null,
                        'handle' => $marketNode['handle'] ?? null,
                        'currency' => $marketNode['catalogs']['nodes'][0]['priceList']['currency']
                            ?? $marketNode['webPresence']['defaultLocale']['locale']
                            ?? null,
                        'enabled' => (bool) ($marketNode['enabled'] ?? true),
                        'is_primary' => (bool) ($marketNode['primary'] ?? false),
                        'raw_payload' => $marketNode,
                    ]
                );

                foreach (($marketNode['catalogs']['nodes'] ?? []) as $catalogNode) {
                    $priceListNode = $catalogNode['priceList'] ?? null;
                    if (!is_array($priceListNode) || empty($priceListNode['id'])) {
                        continue;
                    }

                    $priceList = PriceList::query()->updateOrCreate(
                        [
                            'store_id' => $store->id,
                            'shopify_price_list_id' => $priceListNode['id'],
                        ],
                        [
                            'market_id' => $market->id,
                            'shopify_catalog_id' => $catalogNode['id'] ?? null,
                            'name' => $priceListNode['name'] ?? null,
                            'currency' => $priceListNode['currency'] ?? null,
                            'fixed_prices_count' => (int) ($priceListNode['fixedPricesCount'] ?? 0),
                            'raw_payload' => $priceListNode,
                        ]
                    );
                    $syncedPriceListIds[$priceList->shopify_price_list_id] = true;

                    foreach (($priceListNode['prices']['edges'] ?? []) as $priceEdge) {
                        $priceNode = $priceEdge['node'] ?? null;
                        if (!is_array($priceNode)) {
                            continue;
                        }

                        $variantId = ProductVariant::query()
                            ->where('store_id', $store->id)
                            ->where('shopify_variant_id', $priceNode['variant']['id'] ?? null)
                            ->value('id');

                        PriceListItem::query()->updateOrCreate(
                            [
                                'price_list_id' => $priceList->id,
                                'shopify_variant_id' => $priceNode['variant']['id'] ?? null,
                            ],
                            [
                                'store_id' => $store->id,
                                'product_variant_id' => $variantId,
                                'amount' => $priceNode['price']['amount'] ?? null,
                                'compare_at_amount' => $priceNode['compareAtPrice']['amount'] ?? null,
                                'currency' => $priceNode['price']['currencyCode'] ?? $priceList->currency,
                                'origin_type' => $priceNode['originType'] ?? null,
                                'raw_payload' => $priceNode,
                            ]
                        );
                    }

                    $count++;
                }
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        try {
            $count += $this->syncPriceListsFallback($store, $client, $syncedPriceListIds);
        } catch (Throwable) {
            // Keep the main markets sync successful even if fallback query is not available on this store/API shape.
        }

        return $count;
    }

    private function syncPriceListsFallback(Store $store, ShopifyClient $client, array $syncedPriceListIds): int
    {
        $count = 0;
        $after = null;

        do {
            $response = $client->query(
                query: $this->fallbackQuery(),
                variables: array_filter([
                    'first' => self::PAGE_SIZE,
                    'after' => $after,
                ])
            );

            $connection = $response['data']['priceLists'] ?? null;
            if (!is_array($connection)) {
                break;
            }

            foreach (($connection['edges'] ?? []) as $edge) {
                $priceListNode = $edge['node'] ?? null;
                if (!is_array($priceListNode) || empty($priceListNode['id'])) {
                    continue;
                }

                if (isset($syncedPriceListIds[$priceListNode['id']])) {
                    continue;
                }

                $priceList = PriceList::query()->updateOrCreate(
                    [
                        'store_id' => $store->id,
                        'shopify_price_list_id' => $priceListNode['id'],
                    ],
                    [
                        'market_id' => null,
                        'shopify_catalog_id' => null,
                        'name' => $priceListNode['name'] ?? null,
                        'currency' => $priceListNode['currency'] ?? null,
                        'fixed_prices_count' => (int) ($priceListNode['fixedPricesCount'] ?? 0),
                        'raw_payload' => $priceListNode,
                    ]
                );

                foreach (($priceListNode['prices']['edges'] ?? []) as $priceEdge) {
                    $priceNode = $priceEdge['node'] ?? null;
                    if (!is_array($priceNode)) {
                        continue;
                    }

                    $shopifyVariantId = $priceNode['variant']['id'] ?? null;
                    if (!$shopifyVariantId) {
                        continue;
                    }

                    $variantId = ProductVariant::query()
                        ->where('store_id', $store->id)
                        ->where('shopify_variant_id', $shopifyVariantId)
                        ->value('id');

                    PriceListItem::query()->updateOrCreate(
                        [
                            'price_list_id' => $priceList->id,
                            'shopify_variant_id' => $shopifyVariantId,
                        ],
                        [
                            'store_id' => $store->id,
                            'product_variant_id' => $variantId,
                            'amount' => $priceNode['price']['amount'] ?? null,
                            'compare_at_amount' => $priceNode['compareAtPrice']['amount'] ?? null,
                            'currency' => $priceNode['price']['currencyCode'] ?? $priceList->currency,
                            'origin_type' => $priceNode['originType'] ?? null,
                            'raw_payload' => $priceNode,
                        ]
                    );
                }

                $syncedPriceListIds[$priceListNode['id']] = true;
                $count++;
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $count;
    }

    private function query(): string
    {
        return <<<'GRAPHQL'
query SyncMarketsAndPriceLists($first: Int!, $after: String) {
  markets(first: $first, after: $after) {
    edges {
      node {
        id
        name
        handle
        enabled
        primary
        webPresence {
          defaultLocale {
            locale
          }
        }
        catalogs(first: 20) {
          nodes {
            id
            title
            priceList {
              id
              name
              currency
              fixedPricesCount
              prices(first: 100) {
                edges {
                  node {
                    originType
                    price {
                      amount
                      currencyCode
                    }
                    compareAtPrice {
                      amount
                      currencyCode
                    }
                    variant {
                      id
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

    private function fallbackQuery(): string
    {
        return <<<'GRAPHQL'
query SyncPriceListsFallback($first: Int!, $after: String) {
  priceLists(first: $first, after: $after) {
    edges {
      node {
        id
        name
        currency
        fixedPricesCount
        prices(first: 100) {
          edges {
            node {
              originType
              price {
                amount
                currencyCode
              }
              compareAtPrice {
                amount
                currencyCode
              }
              variant {
                id
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
