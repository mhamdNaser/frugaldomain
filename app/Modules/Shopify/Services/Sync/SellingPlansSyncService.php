<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductSellingPlanGroup;
use App\Modules\Catalog\Models\SellingPlan;
use App\Modules\Catalog\Models\SellingPlanGroup;
use App\Modules\Catalog\Models\SellingPlanSubscription;
use App\Modules\Shopify\Exceptions\ShopifySyncException;
use App\Modules\Shopify\DTOs\SellingPlanGroupData;
use App\Modules\Shopify\DTOs\SellingPlanSubscriptionData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\Customer;
use Illuminate\Support\Facades\Log;

class SellingPlansSyncService
{
    private const PAGE_SIZE = 100;

    public function sync(Store $store): int
    {
        $client = new ShopifyClient($store);
        $count = $this->syncGroupsAndPlans($store, $client);
        $count += $this->syncSubscriptionsSafely($store, $client);

        return $count;
    }

    private function syncSubscriptionsSafely(Store $store, ShopifyClient $client): int
    {
        try {
            return $this->syncSubscriptions($store, $client);
        } catch (ShopifySyncException $e) {
            if (str_contains($e->getMessage(), 'Access denied for subscriptionContracts field')) {
                Log::warning('Skipping subscriptionContracts sync due to missing scope/feature.', [
                    'store_id' => (string) $store->id,
                    'error' => $e->getMessage(),
                ]);

                return 0;
            }

            throw $e;
        }
    }

    private function syncGroupsAndPlans(Store $store, ShopifyClient $client): int
    {
        $count = 0;
        $after = null;

        do {
            $response = $client->query(
                query: $this->groupsQuery(),
                variables: array_filter([
                    'first' => self::PAGE_SIZE,
                    'after' => $after,
                ])
            );

            $connection = $response['data']['sellingPlanGroups'] ?? null;
            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $edge) {
                $node = $edge['node'] ?? null;
                if (!is_array($node) || empty($node['id'])) {
                    continue;
                }

                $data = $this->mapGroup($node);
                $group = SellingPlanGroup::query()->updateOrCreate(
                    [
                        'store_id' => $store->id,
                        'shopify_selling_plan_group_id' => $data->shopifySellingPlanGroupId,
                    ],
                    [
                        'name' => $data->name,
                        'app_id' => $data->appId,
                        'options' => $data->options,
                        'summary' => $data->summary,
                        'raw_payload' => $data->rawPayload,
                    ]
                );

                foreach ($data->plans as $plan) {
                    SellingPlan::query()->updateOrCreate(
                        [
                            'store_id' => $store->id,
                            'shopify_selling_plan_id' => $plan['id'] ?? null,
                        ],
                        [
                            'selling_plan_group_id' => $group->id,
                            'name' => $plan['name'] ?? null,
                            'category' => $plan['category'] ?? null,
                            'billing_policy' => $plan['billingPolicy'] ?? null,
                            'delivery_policy' => $plan['deliveryPolicy'] ?? null,
                            'pricing_policies' => $plan['pricingPolicies'] ?? null,
                            'raw_payload' => $plan,
                        ]
                    );
                }

                foreach ($data->productIds as $shopifyProductId) {
                    $productId = Product::query()
                        ->where('store_id', $store->id)
                        ->where('shopify_product_id', $shopifyProductId)
                        ->value('id');

                    ProductSellingPlanGroup::query()->updateOrCreate(
                        [
                            'product_id' => $productId,
                            'selling_plan_group_id' => $group->id,
                        ],
                        [
                            'store_id' => $store->id,
                            'shopify_product_id' => $shopifyProductId,
                        ]
                    );
                }

                $count++;
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $count;
    }

    private function syncSubscriptions(Store $store, ShopifyClient $client): int
    {
        $count = 0;
        $after = null;

        do {
            $response = $client->query(
                query: $this->subscriptionsQuery(),
                variables: array_filter([
                    'first' => self::PAGE_SIZE,
                    'after' => $after,
                ])
            );

            $connection = $response['data']['subscriptionContracts'] ?? null;
            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $edge) {
                $node = $edge['node'] ?? null;
                if (!is_array($node) || empty($node['id'])) {
                    continue;
                }

                $data = new SellingPlanSubscriptionData(
                    shopifySubscriptionContractId: (string) $node['id'],
                    shopifyCustomerId: $node['customer']['id'] ?? null,
                    status: $node['status'] ?? null,
                    currency: $node['currencyCode'] ?? null,
                    nextBillingAmount: null,
                    nextBillingDate: $node['nextBillingDate'] ?? null,
                    rawPayload: $node,
                );

                $customerId = Customer::query()
                    ->where('store_id', $store->id)
                    ->where('shopify_customer_id', $data->shopifyCustomerId)
                    ->value('id');

                SellingPlanSubscription::query()->updateOrCreate(
                    [
                        'store_id' => $store->id,
                        'shopify_subscription_contract_id' => $data->shopifySubscriptionContractId,
                    ],
                    [
                        'customer_id' => $customerId,
                        'shopify_customer_id' => $data->shopifyCustomerId,
                        'status' => $data->status,
                        'currency' => $data->currency,
                        'next_billing_amount' => $data->nextBillingAmount,
                        'next_billing_date' => $data->nextBillingDate,
                        'raw_payload' => $data->rawPayload,
                    ]
                );

                $count++;
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $count;
    }

    private function mapGroup(array $node): SellingPlanGroupData
    {
        return new SellingPlanGroupData(
            shopifySellingPlanGroupId: (string) $node['id'],
            name: $node['name'] ?? null,
            appId: $node['appId'] ?? null,
            options: $node['options'] ?? [],
            summary: $node['summary'] ?? null,
            productIds: collect($node['products']['edges'] ?? [])
                ->pluck('node.id')
                ->filter()
                ->values()
                ->all(),
            plans: collect($node['sellingPlans']['edges'] ?? [])
                ->pluck('node')
                ->filter(fn ($plan) => is_array($plan))
                ->values()
                ->all(),
            rawPayload: $node,
        );
    }

    private function groupsQuery(): string
    {
        return <<<'GRAPHQL'
query SyncSellingPlanGroups($first: Int!, $after: String) {
  sellingPlanGroups(first: $first, after: $after) {
    edges {
      node {
        id
        name
        appId
        options
        summary
        products(first: 50) {
          edges {
            node {
              id
            }
          }
        }
        sellingPlans(first: 50) {
          edges {
            node {
              id
              name
              category
              billingPolicy {
                ... on SellingPlanRecurringBillingPolicy {
                  interval
                  intervalCount
                }
              }
              deliveryPolicy {
                ... on SellingPlanRecurringDeliveryPolicy {
                  interval
                  intervalCount
                }
              }
              pricingPolicies {
                __typename
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

    private function subscriptionsQuery(): string
    {
        return <<<'GRAPHQL'
query SyncSellingPlanSubscriptions($first: Int!, $after: String) {
  subscriptionContracts(first: $first, after: $after) {
    edges {
      node {
        id
        status
        currencyCode
        nextBillingDate
        customer {
          id
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
