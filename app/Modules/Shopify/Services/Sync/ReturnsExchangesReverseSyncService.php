<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Shopify\Exceptions\ShopifySyncException;
use App\Modules\Fulfillment\Models\Exchange;
use App\Modules\Fulfillment\Models\OrderReturn;
use App\Modules\Fulfillment\Models\OrderReturnItem;
use App\Modules\Fulfillment\Models\ReverseFulfillment;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Shopify\DTOs\ReturnRecordData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;

class ReturnsExchangesReverseSyncService
{
    private const ORDER_PAGE_SIZE = 5;
    private const RETURN_PAGE_SIZE = 5;
    private const RETURN_LINE_ITEM_PAGE_SIZE = 20;
    private const EXCHANGE_LINE_ITEM_PAGE_SIZE = 20;
    private const REVERSE_FULFILLMENT_PAGE_SIZE = 10;

    public function sync(Store $store): int
    {
        $client = new ShopifyClient($store);
        $count = 0;
        $after = null;
        $orderFirst = self::ORDER_PAGE_SIZE;
        $returnFirst = self::RETURN_PAGE_SIZE;
        $returnItemFirst = self::RETURN_LINE_ITEM_PAGE_SIZE;
        $exchangeFirst = self::EXCHANGE_LINE_ITEM_PAGE_SIZE;
        $reverseFirst = self::REVERSE_FULFILLMENT_PAGE_SIZE;

        do {
            try {
                $response = $client->query(
                    query: $this->query(),
                    variables: array_filter([
                        'first' => $orderFirst,
                        'after' => $after,
                        'returnFirst' => $returnFirst,
                        'returnItemFirst' => $returnItemFirst,
                        'exchangeFirst' => $exchangeFirst,
                        'reverseFirst' => $reverseFirst,
                    ])
                );
            } catch (ShopifySyncException $e) {
                if ($this->isCostError($e) && ($orderFirst > 1 || $returnFirst > 1 || $returnItemFirst > 1 || $exchangeFirst > 1 || $reverseFirst > 1)) {
                    $orderFirst = max(1, intdiv($orderFirst, 2));
                    $returnFirst = max(1, intdiv($returnFirst, 2));
                    $returnItemFirst = max(1, intdiv($returnItemFirst, 2));
                    $exchangeFirst = max(1, intdiv($exchangeFirst, 2));
                    $reverseFirst = max(1, intdiv($reverseFirst, 2));
                    continue;
                }

                throw $e;
            }

            $connection = $response['data']['orders'] ?? null;
            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $orderEdge) {
                $orderNode = $orderEdge['node'] ?? null;
                if (!is_array($orderNode) || empty($orderNode['id'])) {
                    continue;
                }

                $order = Order::query()
                    ->where('store_id', $store->id)
                    ->where('shopify_order_id', $orderNode['id'])
                    ->first();

                if (!$order) {
                    continue;
                }

                foreach (($orderNode['returns']['edges'] ?? []) as $returnEdge) {
                    $returnNode = $returnEdge['node'] ?? null;
                    if (!is_array($returnNode) || empty($returnNode['id'])) {
                        continue;
                    }

                    $this->syncReturnRecord($store, $order, $this->mapReturn($returnNode));
                    $count++;
                }
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $count;
    }

    private function syncReturnRecord(Store $store, Order $order, ReturnRecordData $data): void
    {
        $return = OrderReturn::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_return_id' => $data->shopifyReturnId,
            ],
            [
                'order_id' => $order->id,
                'status' => $data->status,
                'name' => $data->name,
                'requested_at' => $data->requestedAt,
                'opened_at' => $data->openedAt,
                'closed_at' => $data->closedAt,
                'raw_payload' => $data->rawPayload,
            ]
        );

        foreach ($data->returnItems as $item) {
            OrderReturnItem::query()->updateOrCreate(
                [
                    'order_return_id' => $return->id,
                    'shopify_return_line_item_id' => $item['shopify_return_line_item_id'] ?? null,
                ],
                [
                    'store_id' => $store->id,
                    'order_item_id' => $this->orderItemId($store, $item['shopify_line_item_id'] ?? null),
                    'shopify_line_item_id' => $item['shopify_line_item_id'] ?? null,
                    'quantity' => (int) ($item['quantity'] ?? 0),
                    'reason' => $item['reason'] ?? null,
                    'note' => $item['note'] ?? null,
                    'raw_payload' => $item['raw_payload'] ?? null,
                ]
            );
        }

        foreach ($data->exchangeItems as $exchange) {
            Exchange::query()->updateOrCreate(
                [
                    'store_id' => $store->id,
                    'shopify_exchange_line_item_id' => $exchange['shopify_exchange_line_item_id'] ?? null,
                ],
                [
                    'order_return_id' => $return->id,
                    'shopify_line_item_id' => $exchange['shopify_line_item_id'] ?? null,
                    'title' => $exchange['title'] ?? null,
                    'quantity' => (int) ($exchange['quantity'] ?? 0),
                    'status' => $exchange['status'] ?? null,
                    'raw_payload' => $exchange['raw_payload'] ?? null,
                ]
            );
        }

        foreach ($data->reverseFulfillments as $reverse) {
            ReverseFulfillment::query()->updateOrCreate(
                [
                    'store_id' => $store->id,
                    'shopify_reverse_fulfillment_order_id' => $reverse['shopify_reverse_fulfillment_order_id'] ?? null,
                ],
                [
                    'order_return_id' => $return->id,
                    'status' => $reverse['status'] ?? null,
                    'raw_payload' => $reverse['raw_payload'] ?? null,
                    'shopify_created_at' => $reverse['shopify_created_at'] ?? null,
                    'shopify_updated_at' => $reverse['shopify_updated_at'] ?? null,
                ]
            );
        }
    }

    private function mapReturn(array $node): ReturnRecordData
    {
        return new ReturnRecordData(
            shopifyReturnId: (string) $node['id'],
            status: $node['status'] ?? null,
            name: $node['name'] ?? null,
            requestedAt: $node['requestApprovedAt'] ?? $node['createdAt'] ?? null,
            openedAt: null,
            closedAt: $node['closedAt'] ?? null,
            returnItems: array_map(function (array $edge): array {
                $item = $edge['node'] ?? [];

                return [
                    'shopify_return_line_item_id' => $item['id'] ?? null,
                    'shopify_line_item_id' => $item['fulfillmentLineItem']['lineItem']['id'] ?? null,
                    'quantity' => $item['quantity'] ?? 0,
                    'reason' => $item['returnReason'] ?? null,
                    'note' => $item['returnReasonNote'] ?? null,
                    'raw_payload' => $item,
                ];
            }, $node['returnLineItems']['edges'] ?? []),
            exchangeItems: array_map(function (array $edge): array {
                $item = $edge['node'] ?? [];

                return [
                    'shopify_exchange_line_item_id' => $item['id'] ?? null,
                    'shopify_line_item_id' => $item['lineItem']['id'] ?? null,
                    'title' => $item['lineItem']['title'] ?? null,
                    'quantity' => $item['quantity'] ?? 0,
                    'status' => $this->exchangeStatus($item),
                    'raw_payload' => $item,
                ];
            }, $node['exchangeLineItems']['edges'] ?? []),
            reverseFulfillments: array_map(function (array $edge): array {
                $item = $edge['node'] ?? [];

                return [
                    'shopify_reverse_fulfillment_order_id' => $item['id'] ?? null,
                    'status' => $item['status'] ?? null,
                    'shopify_created_at' => null,
                    'shopify_updated_at' => null,
                    'raw_payload' => $item,
                ];
            }, $node['reverseFulfillmentOrders']['edges'] ?? []),
            rawPayload: $node,
        );
    }

    private function exchangeStatus(array $item): ?string
    {
        $quantity = (int) ($item['quantity'] ?? 0);
        $processed = (int) ($item['processedQuantity'] ?? 0);

        if ($quantity > 0 && $processed >= $quantity) {
            return 'processed';
        }

        if ($processed > 0) {
            return 'partially_processed';
        }

        return 'unprocessed';
    }

    private function orderItemId(Store $store, ?string $shopifyLineItemId): ?int
    {
        if (!$shopifyLineItemId) {
            return null;
        }

        return OrderItem::query()
            ->where('store_id', $store->id)
            ->where('shopify_line_item_id', $shopifyLineItemId)
            ->value('id');
    }

    private function isCostError(ShopifySyncException $e): bool
    {
        return str_contains($e->getMessage(), 'exceeds the single query max cost limit');
    }

    private function query(): string
    {
        return <<<'GRAPHQL'
query SyncReturns(
  $first: Int!,
  $after: String,
  $returnFirst: Int!,
  $returnItemFirst: Int!,
  $exchangeFirst: Int!,
  $reverseFirst: Int!
) {
  orders(first: $first, after: $after, sortKey: UPDATED_AT, reverse: true) {
    edges {
      node {
        id
        returns(first: $returnFirst) {
          edges {
            node {
              id
              name
              status
              createdAt
              requestApprovedAt
              closedAt
              returnLineItems(first: $returnItemFirst) {
                edges {
                  node {
                    quantity
                    returnReason
                    returnReasonNote
                    ... on ReturnLineItem {
                      id
                      fulfillmentLineItem {
                        lineItem {
                          id
                        }
                      }
                    }
                  }
                }
              }
              exchangeLineItems(first: $exchangeFirst) {
                edges {
                  node {
                    id
                    quantity
                    processedQuantity
                    lineItem {
                      id
                      title
                    }
                  }
                }
              }
              reverseFulfillmentOrders(first: $reverseFirst) {
                edges {
                  node {
                    id
                    status
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
