<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderDuty;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Models\OrderItemDuty;
use App\Modules\Shopify\DTOs\OrderDutyBreakdownData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;

class OrderDutiesSyncService
{
    public function sync(Store $store): int
    {
        $client = new ShopifyClient($store);
        $count = 0;

        Order::query()
            ->where('store_id', $store->id)
            ->whereNotNull('shopify_order_id')
            ->orderBy('id')
            ->chunkById(50, function ($orders) use ($store, $client, &$count): void {
                foreach ($orders as $order) {
                    $response = $client->query(
                        query: $this->query(),
                        variables: ['id' => $order->shopify_order_id]
                    );

                    $node = $response['data']['node'] ?? null;
                    if (!is_array($node)) {
                        continue;
                    }

                    $this->persist($store, $order, $this->map($node));
                    $count++;
                }
            });

        return $count;
    }

    private function persist(Store $store, Order $order, OrderDutyBreakdownData $data): void
    {
        foreach ($data->lineItemDuties as $duty) {
            $orderDuty = OrderDuty::query()->updateOrCreate(
                [
                    'store_id' => $store->id,
                    'shopify_order_id' => $data->shopifyOrderId,
                    'shopify_duty_id' => $duty['shopify_duty_id'] ?? null,
                ],
                [
                    'order_id' => $order->id,
                    'harmonized_system_code' => $duty['harmonized_system_code'] ?? null,
                    'amount' => (float) ($duty['amount'] ?? 0),
                    'currency' => $duty['currency'] ?? $data->currency,
                    'raw_payload' => $duty['raw_payload'] ?? [],
                ]
            );

            OrderItemDuty::query()->updateOrCreate(
                [
                    'store_id' => $store->id,
                    'shopify_line_item_id' => $duty['shopify_line_item_id'] ?? null,
                    'shopify_duty_id' => $duty['shopify_duty_id'] ?? null,
                ],
                [
                    'order_item_id' => $this->orderItemId($store, $duty['shopify_line_item_id'] ?? null),
                    'order_duty_id' => $orderDuty->id,
                    'harmonized_system_code' => $duty['harmonized_system_code'] ?? null,
                    'amount' => (float) ($duty['amount'] ?? 0),
                    'currency' => $duty['currency'] ?? $data->currency,
                    'raw_payload' => $duty['raw_payload'] ?? [],
                ]
            );
        }
    }

    private function map(array $orderNode): OrderDutyBreakdownData
    {
        $lineItemDuties = [];

        foreach (($orderNode['lineItems']['edges'] ?? []) as $lineEdge) {
            $line = $lineEdge['node'] ?? [];
            $lineItemId = $line['id'] ?? null;

            foreach (($line['duties'] ?? []) as $duty) {
                $lineItemDuties[] = [
                    'shopify_line_item_id' => $lineItemId,
                    'shopify_duty_id' => $duty['id'] ?? null,
                    'harmonized_system_code' => $duty['harmonizedSystemCode'] ?? null,
                    'amount' => (float) ($duty['price']['shopMoney']['amount'] ?? 0),
                    'currency' => $duty['price']['shopMoney']['currencyCode'] ?? null,
                    'raw_payload' => $duty,
                ];
            }
        }

        return new OrderDutyBreakdownData(
            shopifyOrderId: (string) ($orderNode['id'] ?? ''),
            orderDutyTotal: (float) ($orderNode['currentTotalDutiesSet']['shopMoney']['amount'] ?? 0),
            currency: $orderNode['currentTotalDutiesSet']['shopMoney']['currencyCode'] ?? null,
            lineItemDuties: $lineItemDuties,
            rawPayload: $orderNode,
        );
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

    private function query(): string
    {
        return <<<'GRAPHQL'
query SyncOrderDuties($id: ID!) {
  node(id: $id) {
    ... on Order {
      id
      currentTotalDutiesSet {
        shopMoney {
          amount
          currencyCode
        }
      }
      lineItems(first: 100) {
        edges {
          node {
            id
            duties {
              id
              harmonizedSystemCode
              price {
                shopMoney {
                  amount
                  currencyCode
                }
              }
            }
          }
        }
      }
    }
  }
}
GRAPHQL;
    }
}

