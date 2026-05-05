<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Shopify\DTOs\OrderData;
use App\Modules\Shopify\DTOs\OrderItemData;
use App\Modules\Shopify\DTOs\TaxLineData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use App\Modules\Tax\Models\TaxLine;
use App\Modules\User\Models\Customer;

class OrdersSyncService
{
    private const PAGE_SIZE = 50;

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
                ]),
            );

            $connection = $response['data']['orders'] ?? null;

            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $edge) {
                $node = $edge['node'] ?? null;

                if (is_array($node)) {
                    $this->syncOrder($store, $this->orderData($client, $node));
                    $count++;
                }
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $count;
    }

    private function syncOrder(Store $store, OrderData $data): Order
    {
        $order = Order::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_order_id' => $data->shopifyOrderId,
            ],
            [
                'shopify_customer_id' => $data->shopifyCustomerId,
                'customer_id' => $this->customerId($store, $data->shopifyCustomerId),
                'email' => $data->email,
                'order_number' => $data->orderNumber,
                'status' => $data->status,
                'payment_status' => $data->paymentStatus,
                'fulfillment_status' => $data->fulfillmentStatus,
                'subtotal' => $data->subtotal,
                'tax' => $data->tax,
                'shipping' => $data->shipping,
                'discount' => $data->discount,
                'total' => $data->total,
                'currency' => $data->currency,
                'placed_at' => $data->placedAt,
                'raw_payload' => $data->rawPayload,
                'shopify_created_at' => $data->shopifyCreatedAt,
                'shopify_updated_at' => $data->shopifyUpdatedAt,
            ]
        );

        foreach ($data->items as $itemData) {
            if ($itemData instanceof OrderItemData) {
                $this->syncOrderItem($store, $order, $itemData);
            }
        }

        $this->syncTaxLines($store, $order, null, $data->taxLines, 'shopify_order');

        return $order;
    }

    private function lineItems(ShopifyClient $client, array $order): array
    {
        $lineItems = $order['lineItems']['edges'] ?? [];
        $pageInfo = $order['lineItems']['pageInfo'] ?? [];
        $after = $pageInfo['endCursor'] ?? null;

        while (!empty($pageInfo['hasNextPage']) && !empty($after) && !empty($order['id'])) {
            $response = $client->query(
                query: $this->lineItemsQuery(),
                variables: [
                    'id' => $order['id'],
                    'after' => $after,
                ],
            );

            $connection = $response['data']['node']['lineItems'] ?? null;

            if (!is_array($connection)) {
                break;
            }

            $lineItems = array_merge($lineItems, $connection['edges'] ?? []);
            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        }

        return $lineItems;
    }

    private function syncOrderItem(Store $store, Order $order, OrderItemData $data): void
    {
        $item = OrderItem::query()->updateOrCreate(
            [
                'order_id' => $order->id,
                'shopify_line_item_id' => $data->shopifyLineItemId,
            ],
            [
                'store_id' => $store->id,
                'variant_id' => $this->variantId($store, $data->shopifyVariantId),
                'shopify_product_id' => $data->shopifyProductId,
                'shopify_variant_id' => $data->shopifyVariantId,
                'product_title' => $data->productTitle,
                'variant_title' => $data->variantTitle,
                'sku' => $data->sku,
                'quantity' => $data->quantity,
                'unit_price' => $data->unitPrice,
                'total_price' => $data->totalPrice,
                'raw_payload' => $data->rawPayload,
            ]
        );

        $this->syncTaxLines($store, $order, $item, $data->taxLines, 'shopify_line_item');
    }

    private function syncTaxLines(Store $store, Order $order, ?OrderItem $item, array $taxLines, string $source): void
    {
        $keys = [];

        foreach ($taxLines as $taxLine) {
            if (!$taxLine instanceof TaxLineData) {
                continue;
            }

            $key = $this->taxLineKey($order, $item, $taxLine, $source);
            $keys[] = $key;

            TaxLine::query()->updateOrCreate(
                [
                    'order_id' => $order->id,
                    'order_item_id' => $item?->id,
                    'source_key' => $key,
                ],
                [
                    'store_id' => $store->id,
                    'shopify_tax_line_id' => $taxLine->shopifyTaxLineId,
                    'title' => $taxLine->title,
                    'rate' => $taxLine->rate,
                    'rate_percentage' => $taxLine->ratePercentage,
                    'price' => $taxLine->price,
                    'currency' => $taxLine->currency ?: $order->currency,
                    'channel_liable' => $taxLine->channelLiable,
                    'source' => $source,
                    'is_shipping' => $taxLine->isShipping,
                    'raw_payload' => $taxLine->rawPayload,
                ]
            );
        }

        $query = TaxLine::query()
            ->where('order_id', $order->id)
            ->where('source', $source);

        $item
            ? $query->where('order_item_id', $item->id)
            : $query->whereNull('order_item_id');

        $keys
            ? $query->whereNotIn('source_key', $keys)->delete()
            : $query->delete();
    }

    private function taxLineKey(Order $order, ?OrderItem $item, TaxLineData $taxLine, string $source): string
    {
        return hash('sha256', implode('|', [
            $source,
            $order->shopify_order_id,
            $item?->shopify_line_item_id,
            $taxLine->shopifyTaxLineId,
            $taxLine->title,
            $taxLine->rate,
            $taxLine->ratePercentage,
            $taxLine->price,
            $taxLine->currency,
        ]));
    }

    private function variantId(Store $store, ?string $shopifyVariantId): ?int
    {
        if (!$shopifyVariantId) {
            return null;
        }

        return ProductVariant::query()
            ->where('store_id', $store->id)
            ->where('shopify_variant_id', $shopifyVariantId)
            ->value('id');
    }

    private function customerId(Store $store, ?string $shopifyCustomerId): ?int
    {
        if (!$shopifyCustomerId) {
            return null;
        }

        return Customer::query()
            ->where('store_id', $store->id)
            ->where('shopify_customer_id', $shopifyCustomerId)
            ->value('id');
    }

    private function amount(array $moneySet): float
    {
        return (float) ($moneySet['shopMoney']['amount'] ?? 0);
    }

    private function orderData(ShopifyClient $client, array $node): OrderData
    {
        return new OrderData(
            shopifyOrderId: $node['id'],
            shopifyCustomerId: $node['customer']['id'] ?? null,
            email: $node['email'] ?? $node['customer']['email'] ?? null,
            orderNumber: $node['name'] ?? null,
            status: strtolower((string) ($node['displayFulfillmentStatus'] ?? 'pending')),
            paymentStatus: strtolower((string) ($node['displayFinancialStatus'] ?? 'pending')),
            fulfillmentStatus: strtolower((string) ($node['displayFulfillmentStatus'] ?? 'pending')),
            subtotal: $this->amount($node['subtotalPriceSet'] ?? []),
            tax: $this->amount($node['totalTaxSet'] ?? []),
            shipping: $this->amount($node['totalShippingPriceSet'] ?? []),
            discount: $this->amount($node['totalDiscountsSet'] ?? []),
            total: $this->amount($node['totalPriceSet'] ?? []),
            currency: $node['currencyCode'] ?? null,
            placedAt: $node['processedAt'] ?? $node['createdAt'] ?? null,
            shopifyCreatedAt: $node['createdAt'] ?? null,
            shopifyUpdatedAt: $node['updatedAt'] ?? null,
            rawPayload: $node,
            items: array_values(array_filter(array_map(
                fn (array $edge): ?OrderItemData => $this->orderItemData($edge['node'] ?? null),
                $this->lineItems($client, $node),
            ))),
            taxLines: $this->taxLineDataList($node['taxLines'] ?? []),
        );
    }

    private function orderItemData(mixed $line): ?OrderItemData
    {
        if (!is_array($line)) {
            return null;
        }

        return new OrderItemData(
            shopifyLineItemId: $line['id'] ?? null,
            shopifyProductId: $line['product']['id'] ?? null,
            shopifyVariantId: $line['variant']['id'] ?? null,
            productTitle: $line['title'] ?? $line['product']['title'] ?? 'Unknown product',
            variantTitle: $line['variantTitle'] ?? null,
            sku: $line['sku'] ?? null,
            quantity: (int) ($line['quantity'] ?? 0),
            unitPrice: $this->amount($line['originalUnitPriceSet'] ?? []),
            totalPrice: $this->amount($line['discountedTotalSet'] ?? []),
            rawPayload: $line,
            taxLines: $this->taxLineDataList($line['taxLines'] ?? []),
        );
    }

    private function taxLineDataList(array $taxLines, bool $isShipping = false): array
    {
        return array_values(array_filter(array_map(
            fn (array $line): ?TaxLineData => $this->taxLineData($line, $isShipping),
            $taxLines,
        )));
    }

    private function taxLineData(array $line, bool $isShipping = false): ?TaxLineData
    {
        if ($line === []) {
            return null;
        }

        $priceSet = $line['priceSet'] ?? $line['price'] ?? [];

        return new TaxLineData(
            shopifyTaxLineId: $line['id'] ?? null,
            title: $line['title'] ?? null,
            rate: (float) ($line['rate'] ?? 0),
            ratePercentage: (float) ($line['ratePercentage'] ?? (($line['rate'] ?? 0) * 100)),
            price: $this->amount($priceSet),
            currency: $priceSet['shopMoney']['currencyCode'] ?? null,
            channelLiable: array_key_exists('channelLiable', $line) ? (bool) $line['channelLiable'] : null,
            isShipping: $isShipping,
            rawPayload: $line,
        );
    }

    private function query(): string
    {
        return <<<'GRAPHQL'
query GetOrders($first: Int!, $after: String) {
  orders(first: $first, after: $after, sortKey: CREATED_AT, reverse: true) {
    edges {
      node {
        id
        name
        email
        currencyCode
        displayFinancialStatus
        displayFulfillmentStatus
        processedAt
        createdAt
        updatedAt
        customer {
          id
          email
        }
        subtotalPriceSet {
          shopMoney {
            amount
            currencyCode
          }
        }
        totalTaxSet {
          shopMoney {
            amount
            currencyCode
          }
        }
        totalShippingPriceSet {
          shopMoney {
            amount
            currencyCode
          }
        }
        totalDiscountsSet {
          shopMoney {
            amount
            currencyCode
          }
        }
        totalPriceSet {
          shopMoney {
            amount
            currencyCode
          }
        }
        taxLines {
          title
          rate
          ratePercentage
          channelLiable
          priceSet {
            shopMoney {
              amount
              currencyCode
            }
          }
        }
        lineItems(first: 100) {
          edges {
            node {
              ...OrderLineItemFields
            }
          }
          pageInfo {
            hasNextPage
            endCursor
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

fragment OrderLineItemFields on LineItem {
  id
  title
  quantity
  sku
  variantTitle
  originalUnitPriceSet {
    shopMoney {
      amount
      currencyCode
    }
  }
  discountedTotalSet {
    shopMoney {
      amount
      currencyCode
    }
  }
  taxLines {
    title
    rate
    ratePercentage
    channelLiable
    priceSet {
      shopMoney {
        amount
        currencyCode
      }
    }
  }
  product {
    id
    title
  }
  variant {
    id
  }
}
GRAPHQL;
    }

    private function lineItemsQuery(): string
    {
        return <<<'GRAPHQL'
query GetOrderLineItems($id: ID!, $after: String) {
  node(id: $id) {
    ... on Order {
      lineItems(first: 100, after: $after) {
        edges {
          node {
            ...OrderLineItemFields
          }
        }
        pageInfo {
          hasNextPage
          endCursor
        }
      }
    }
  }
}

fragment OrderLineItemFields on LineItem {
  id
  title
  quantity
  sku
  variantTitle
  originalUnitPriceSet {
    shopMoney {
      amount
      currencyCode
    }
  }
  discountedTotalSet {
    shopMoney {
      amount
      currencyCode
    }
  }
  taxLines {
    title
    rate
    ratePercentage
    channelLiable
    priceSet {
      shopMoney {
        amount
        currencyCode
      }
    }
  }
  product {
    id
    title
  }
  variant {
    id
  }
}
GRAPHQL;
    }
}
