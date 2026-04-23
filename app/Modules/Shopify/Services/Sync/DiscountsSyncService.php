<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Marketing\Models\Discount;
use App\Modules\Marketing\Models\DiscountCode;
use App\Modules\Marketing\Models\DiscountUsage;
use App\Modules\Shopify\DTOs\DiscountCodeData;
use App\Modules\Shopify\DTOs\DiscountData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;

class DiscountsSyncService
{
    private const PAGE_SIZE = 50;
    private const CODES_PAGE_SIZE = 100;

    public function sync(Store $store): int
    {
        $client = new ShopifyClient($store);
        $count = 0;
        $after = null;

        do {
            $response = $client->query(
                query: $this->query(),
                variables: array_filter([
                    'first' => self::PAGE_SIZE,
                    'after' => $after,
                    'codesFirst' => self::CODES_PAGE_SIZE,
                ]),
            );

            $connection = $response['data']['discountNodes'] ?? null;

            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $edge) {
                $node = $edge['node'] ?? null;

                if (is_array($node)) {
                    $this->syncDiscount($store, $this->discountData($node));
                    $count++;
                }
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $count;
    }

    private function syncDiscount(Store $store, DiscountData $data): Discount
    {
        $discount = Discount::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_discount_id' => $data->shopifyDiscountId,
            ],
            [
                'discount_type' => $data->discountType,
                'method' => $data->method,
                'title' => $data->title,
                'status' => $data->status,
                'summary' => $data->summary,
                'short_summary' => $data->shortSummary,
                'usage_limit' => $data->usageLimit,
                'usage_count' => $data->usageCount,
                'total_sales' => $data->totalSales,
                'currency' => $data->currency,
                'starts_at' => $data->startsAt,
                'ends_at' => $data->endsAt,
                'raw_payload' => $data->rawPayload,
                'shopify_updated_at' => $data->shopifyUpdatedAt,
            ],
        );

        foreach ($data->codes as $codeData) {
            if ($codeData instanceof DiscountCodeData) {
                DiscountCode::query()->updateOrCreate(
                    [
                        'discount_id' => $discount->id,
                        'shopify_discount_code_id' => $codeData->shopifyDiscountCodeId,
                    ],
                    [
                        'store_id' => $store->id,
                        'code' => $codeData->code,
                        'usage_count' => $codeData->usageCount,
                        'raw_payload' => $codeData->rawPayload,
                    ],
                );
            }
        }

        DiscountUsage::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'discount_id' => $discount->id,
                'order_id' => null,
                'shopify_order_id' => null,
                'code' => null,
            ],
            [
                'usage_count' => $data->usageCount,
                'total_sales' => $data->totalSales,
                'currency' => $data->currency,
                'raw_payload' => [
                    'source' => 'discount_aggregate',
                    'discount_node_id' => $data->shopifyDiscountId,
                ],
            ],
        );

        return $discount;
    }

    private function discountData(array $node): DiscountData
    {
        $discountNode = $node['discount'] ?? [];
        $type = $discountNode['__typename'] ?? 'UnknownDiscount';

        return new DiscountData(
            shopifyDiscountId: $node['id'],
            discountType: $type,
            method: str_starts_with($type, 'DiscountAutomatic') ? 'automatic' : 'code',
            title: $discountNode['title'] ?? null,
            status: strtolower((string) ($discountNode['status'] ?? '')),
            summary: $discountNode['summary'] ?? null,
            shortSummary: $discountNode['shortSummary'] ?? null,
            usageLimit: $discountNode['usageLimit'] ?? null,
            usageCount: (int) ($discountNode['asyncUsageCount'] ?? 0),
            totalSales: (float) ($discountNode['totalSales']['amount'] ?? 0),
            currency: $discountNode['totalSales']['currencyCode'] ?? null,
            startsAt: $discountNode['startsAt'] ?? null,
            endsAt: $discountNode['endsAt'] ?? null,
            shopifyUpdatedAt: $discountNode['updatedAt'] ?? null,
            rawPayload: $node,
            codes: array_values(array_filter(array_map(
                fn (array $edge): ?DiscountCodeData => $this->discountCodeData($edge['node'] ?? null),
                $discountNode['codes']['edges'] ?? [],
            ))),
        );
    }

    private function discountCodeData(mixed $node): ?DiscountCodeData
    {
        if (!is_array($node)) {
            return null;
        }

        return new DiscountCodeData(
            shopifyDiscountCodeId: $node['id'] ?? null,
            code: $node['code'] ?? null,
            usageCount: (int) ($node['asyncUsageCount'] ?? 0),
            rawPayload: $node,
        );
    }

    private function query(): string
    {
        return <<<'GRAPHQL'
query GetDiscounts($first: Int!, $after: String, $codesFirst: Int!) {
  discountNodes(first: $first, after: $after, sortKey: UPDATED_AT, reverse: true) {
    edges {
      node {
        id
        discount {
          __typename
          ... on DiscountCodeBasic {
            title
            summary
            shortSummary
            status
            startsAt
            endsAt
            usageLimit
            asyncUsageCount
            totalSales {
              amount
              currencyCode
            }
            updatedAt
            codes(first: $codesFirst) {
              edges {
                node {
                  id
                  code
                  asyncUsageCount
                }
              }
            }
          }
          ... on DiscountCodeBxgy {
            title
            summary
            status
            startsAt
            endsAt
            usageLimit
            asyncUsageCount
            totalSales {
              amount
              currencyCode
            }
            updatedAt
            codes(first: $codesFirst) {
              edges {
                node {
                  id
                  code
                  asyncUsageCount
                }
              }
            }
          }
          ... on DiscountCodeFreeShipping {
            title
            summary
            shortSummary
            status
            startsAt
            endsAt
            usageLimit
            asyncUsageCount
            totalSales {
              amount
              currencyCode
            }
            updatedAt
            codes(first: $codesFirst) {
              edges {
                node {
                  id
                  code
                  asyncUsageCount
                }
              }
            }
          }
          ... on DiscountCodeApp {
            title
            status
            startsAt
            endsAt
            usageLimit
            asyncUsageCount
            totalSales {
              amount
              currencyCode
            }
            updatedAt
            codes(first: $codesFirst) {
              edges {
                node {
                  id
                  code
                  asyncUsageCount
                }
              }
            }
          }
          ... on DiscountAutomaticBasic {
            title
            summary
            shortSummary
            status
            startsAt
            endsAt
            asyncUsageCount
            updatedAt
          }
          ... on DiscountAutomaticBxgy {
            title
            summary
            status
            startsAt
            endsAt
            asyncUsageCount
            updatedAt
          }
          ... on DiscountAutomaticFreeShipping {
            title
            summary
            shortSummary
            status
            startsAt
            endsAt
            asyncUsageCount
            updatedAt
          }
          ... on DiscountAutomaticApp {
            title
            status
            startsAt
            endsAt
            asyncUsageCount
            updatedAt
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
