<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Shopify\Exceptions\ShopifySyncException;
use App\Modules\Shopify\DTOs\ShippingMethodData;
use App\Modules\Shopify\DTOs\ShippingRateData;
use App\Modules\Shopify\DTOs\ShippingZoneData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ShippingProfilesSyncService
{
    private const PROFILE_PAGE_SIZE = 5;
    private const ZONE_PAGE_SIZE = 10;
    private const METHOD_PAGE_SIZE = 10;

    public function sync(Store $store): int
    {
        $this->assertRequiredColumns();

        $client = new ShopifyClient($store);
        $after = null;
        $processed = 0;
        $profileFirst = self::PROFILE_PAGE_SIZE;
        $zoneFirst = self::ZONE_PAGE_SIZE;
        $methodFirst = self::METHOD_PAGE_SIZE;

        do {
            try {
                $response = $client->query(
                    query: $this->query(),
                    variables: array_filter([
                        'first' => $profileFirst,
                        'after' => $after,
                        'zoneFirst' => $zoneFirst,
                        'methodFirst' => $methodFirst,
                    ])
                );
            } catch (ShopifySyncException $e) {
                if ($this->isCostError($e) && ($profileFirst > 1 || $zoneFirst > 1 || $methodFirst > 1)) {
                    $profileFirst = max(1, intdiv($profileFirst, 2));
                    $zoneFirst = max(1, intdiv($zoneFirst, 2));
                    $methodFirst = max(1, intdiv($methodFirst, 2));
                    continue;
                }

                throw $e;
            }

            $connection = $response['data']['deliveryProfiles'] ?? null;

            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $edge) {
                $profile = $edge['node'] ?? null;

                if (!is_array($profile)) {
                    continue;
                }

                $profileId = (string) ($profile['id'] ?? '');
                $locationGroups = $profile['profileLocationGroups'] ?? [];

                foreach ($locationGroups as $locationGroup) {
                    $zones = $locationGroup['locationGroupZones']['edges'] ?? [];

                    foreach ($zones as $zoneEdge) {
                        $zoneNode = $zoneEdge['node'] ?? null;

                        if (!is_array($zoneNode) || empty($zoneNode['zone']['id'])) {
                            continue;
                        }

                        $zoneData = new ShippingZoneData(
                            shopifyZoneId: (string) $zoneNode['zone']['id'],
                            shopifyProfileId: $profileId,
                            name: $zoneNode['zone']['name'] ?? null,
                            countries: $zoneNode['zone']['countries'] ?? [],
                            rawPayload: $zoneNode['zone'],
                        );

                        $zoneId = $this->upsertZone($store->id, $zoneData);
                        $methodEdges = $zoneNode['methodDefinitions']['edges'] ?? [];

                        foreach ($methodEdges as $methodEdge) {
                            $methodNode = $methodEdge['node'] ?? null;

                            if (!is_array($methodNode) || empty($methodNode['id'])) {
                                continue;
                            }

                            $methodData = new ShippingMethodData(
                                shopifyMethodId: (string) $methodNode['id'],
                                shopifyZoneId: $zoneData->shopifyZoneId,
                                name: $methodNode['name'] ?? null,
                                description: $methodNode['description'] ?? null,
                                active: (bool) ($methodNode['active'] ?? true),
                                methodType: $methodNode['rateProvider']['__typename'] ?? null,
                                conditions: $methodNode['methodConditions'] ?? [],
                                rawPayload: $methodNode,
                            );

                            $methodId = $this->upsertMethod($store->id, $zoneId, $methodData);

                            foreach ($this->extractRates($methodData) as $rateData) {
                                $this->upsertRate($store->id, $zoneId, $methodId, $rateData);
                            }
                        }

                        $processed++;
                    }
                }
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $processed;
    }

    private function upsertZone(string $storeId, ShippingZoneData $data): int
    {
        DB::table('shipping_zones')->updateOrInsert(
            [
                'store_id' => $storeId,
                'shopify_zone_id' => $data->shopifyZoneId,
            ],
            [
                'shopify_profile_id' => $data->shopifyProfileId,
                'name' => $data->name,
                'countries' => json_encode($data->countries),
                'raw_payload' => json_encode($data->rawPayload),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return (int) DB::table('shipping_zones')
            ->where('store_id', $storeId)
            ->where('shopify_zone_id', $data->shopifyZoneId)
            ->value('id');
    }

    private function upsertMethod(string $storeId, int $zoneId, ShippingMethodData $data): int
    {
        DB::table('shipping_methods')->updateOrInsert(
            [
                'store_id' => $storeId,
                'shopify_method_id' => $data->shopifyMethodId,
            ],
            [
                'shipping_zone_id' => $zoneId,
                'shopify_zone_id' => $data->shopifyZoneId,
                'name' => $data->name,
                'description' => $data->description,
                'is_active' => $data->active,
                'method_type' => $data->methodType,
                'conditions' => json_encode($data->conditions),
                'raw_payload' => json_encode($data->rawPayload),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return (int) DB::table('shipping_methods')
            ->where('store_id', $storeId)
            ->where('shopify_method_id', $data->shopifyMethodId)
            ->value('id');
    }

    /**
     * @return array<int, ShippingRateData>
     */
    private function extractRates(ShippingMethodData $methodData): array
    {
        $rates = [];
        $rateProvider = $methodData->rawPayload['rateProvider'] ?? null;

        if (($rateProvider['__typename'] ?? null) === 'DeliveryRateDefinition') {
            $rates[] = new ShippingRateData(
                shopifyRateId: $methodData->shopifyMethodId . ':fixed',
                shopifyMethodId: $methodData->shopifyMethodId,
                shopifyZoneId: $methodData->shopifyZoneId,
                name: $methodData->name,
                amount: isset($rateProvider['price']['amount']) ? (float) $rateProvider['price']['amount'] : null,
                currency: $rateProvider['price']['currencyCode'] ?? null,
                rawPayload: $rateProvider,
            );
        }

        foreach ($methodData->conditions as $idx => $condition) {
            $criteria = $condition['conditionCriteria'] ?? null;
            $amount = null;
            $currency = null;

            if (is_array($criteria) && isset($criteria['amount'])) {
                $amount = (float) $criteria['amount'];
                $currency = $criteria['currencyCode'] ?? null;
            }

            $rates[] = new ShippingRateData(
                shopifyRateId: $methodData->shopifyMethodId . ':' . (string) $idx,
                shopifyMethodId: $methodData->shopifyMethodId,
                shopifyZoneId: $methodData->shopifyZoneId,
                name: $condition['field'] ?? $methodData->name,
                amount: $amount,
                currency: $currency,
                rawPayload: $condition,
            );
        }

        if ($rates !== []) {
            return $rates;
        }

        return [
            new ShippingRateData(
                shopifyRateId: $methodData->shopifyMethodId . ':default',
                shopifyMethodId: $methodData->shopifyMethodId,
                shopifyZoneId: $methodData->shopifyZoneId,
                name: $methodData->name,
                amount: null,
                currency: null,
                rawPayload: $methodData->rawPayload,
            ),
        ];
    }

    private function upsertRate(string $storeId, int $zoneId, int $methodId, ShippingRateData $data): void
    {
        DB::table('shipping_rates')->updateOrInsert(
            [
                'store_id' => $storeId,
                'shopify_rate_id' => $data->shopifyRateId,
            ],
            [
                'shipping_zone_id' => $zoneId,
                'shipping_method_id' => $methodId,
                'shopify_method_id' => $data->shopifyMethodId,
                'shopify_zone_id' => $data->shopifyZoneId,
                'name' => $data->name,
                'amount' => $data->amount,
                'currency' => $data->currency,
                'raw_payload' => json_encode($data->rawPayload),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function assertRequiredColumns(): void
    {
        $required = [
            'shipping_zones' => ['store_id', 'shopify_zone_id'],
            'shipping_methods' => ['store_id', 'shopify_method_id', 'shipping_zone_id'],
            'shipping_rates' => ['store_id', 'shopify_rate_id', 'shipping_method_id', 'shipping_zone_id'],
        ];

        foreach ($required as $table => $columns) {
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    throw new RuntimeException(
                        sprintf('Shipping sync requires column `%s.%s`. Please run shipping sync migrations first.', $table, $column)
                    );
                }
            }
        }
    }

    private function isCostError(ShopifySyncException $e): bool
    {
        return str_contains($e->getMessage(), 'exceeds the single query max cost limit');
    }

    private function query(): string
    {
        return <<<'GRAPHQL'
query SyncDeliveryProfiles($first: Int!, $after: String, $zoneFirst: Int!, $methodFirst: Int!) {
  deliveryProfiles(first: $first, after: $after) {
    edges {
      node {
        id
        profileLocationGroups {
          locationGroup {
            id
          }
          locationGroupZones(first: $zoneFirst) {
            edges {
              node {
                zone {
                  id
                  name
                  countries {
                    code {
                      countryCode
                      restOfWorld
                    }
                    provinces {
                      name
                      code
                    }
                  }
                }
                methodDefinitions(first: $methodFirst) {
                  edges {
                    node {
                      id
                      name
                      active
                      description
                      rateProvider {
                        __typename
                        ... on DeliveryRateDefinition {
                          id
                          price {
                            amount
                            currencyCode
                          }
                        }
                      }
                      methodConditions {
                        field
                        operator
                        conditionCriteria {
                          __typename
                          ... on MoneyV2 {
                            amount
                            currencyCode
                          }
                          ... on Weight {
                            unit
                            value
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
