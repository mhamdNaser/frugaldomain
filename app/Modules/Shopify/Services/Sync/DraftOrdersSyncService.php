<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Models\DraftOrder;
use App\Modules\Orders\Models\DraftOrderItem;
use App\Modules\Shopify\DTOs\DraftOrderData;
use App\Modules\Shopify\DTOs\OrderItemData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\Customer;

class DraftOrdersSyncService
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

            $connection = $response['data']['draftOrders'] ?? null;

            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $edge) {
                $node = $edge['node'] ?? null;

                if (is_array($node)) {
                    $this->syncDraftOrder($store, $this->draftOrderData($client, $node));
                    $count++;
                }
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $count;
    }

    private function syncDraftOrder(Store $store, DraftOrderData $data): DraftOrder
    {
        $draftOrder = DraftOrder::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_draft_order_id' => $data->shopifyDraftOrderId,
            ],
            [
                'shopify_customer_id' => $data->shopifyCustomerId,
                'customer_id' => $this->customerId($store, $data->shopifyCustomerId),
                'name' => $data->name,
                'status' => $data->status,
                'invoice_url' => $data->invoiceUrl,
                'subtotal' => $data->subtotal,
                'tax' => $data->tax,
                'total' => $data->total,
                'currency' => $data->currency,
                'completed_at' => $data->completedAt,
                'raw_payload' => $data->rawPayload,
                'shopify_created_at' => $data->shopifyCreatedAt,
                'shopify_updated_at' => $data->shopifyUpdatedAt,
            ]
        );

        foreach ($data->items as $itemData) {
            if ($itemData instanceof OrderItemData) {
                $this->syncDraftOrderItem($store, $draftOrder, $itemData);
            }
        }

        return $draftOrder;
    }

    private function lineItems(ShopifyClient $client, array $draftOrder): array
    {
        $lineItems = $draftOrder['lineItems']['edges'] ?? [];
        $pageInfo = $draftOrder['lineItems']['pageInfo'] ?? [];
        $after = $pageInfo['endCursor'] ?? null;

        while (!empty($pageInfo['hasNextPage']) && !empty($after) && !empty($draftOrder['id'])) {
            $response = $client->query(
                query: $this->lineItemsQuery(),
                variables: [
                    'id' => $draftOrder['id'],
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

    private function syncDraftOrderItem(Store $store, DraftOrder $draftOrder, OrderItemData $data): void
    {
        DraftOrderItem::query()->updateOrCreate(
            [
                'draft_order_id' => $draftOrder->id,
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

    private function draftOrderData(ShopifyClient $client, array $node): DraftOrderData
    {
        return new DraftOrderData(
            shopifyDraftOrderId: $node['id'],
            shopifyCustomerId: $node['customer']['id'] ?? null,
            name: $node['name'] ?? null,
            status: strtolower((string) ($node['status'] ?? 'open')),
            invoiceUrl: $node['invoiceUrl'] ?? null,
            subtotal: $this->amount($node['subtotalPriceSet'] ?? []),
            tax: $this->amount($node['totalTaxSet'] ?? []),
            total: $this->amount($node['totalPriceSet'] ?? []),
            currency: $node['currencyCode'] ?? null,
            completedAt: $node['completedAt'] ?? null,
            shopifyCreatedAt: $node['createdAt'] ?? null,
            shopifyUpdatedAt: $node['updatedAt'] ?? null,
            rawPayload: $node,
            items: array_values(array_filter(array_map(
                fn (array $edge): ?OrderItemData => $this->draftOrderItemData($edge['node'] ?? null),
                $this->lineItems($client, $node),
            ))),
        );
    }

    private function draftOrderItemData(mixed $line): ?OrderItemData
    {
        if (!is_array($line)) {
            return null;
        }

        return new OrderItemData(
            shopifyLineItemId: $line['id'] ?? null,
            shopifyProductId: $line['product']['id'] ?? null,
            shopifyVariantId: $line['variant']['id'] ?? null,
            productTitle: $line['title'] ?? $line['product']['title'] ?? 'Custom item',
            variantTitle: $line['variantTitle'] ?? null,
            sku: $line['sku'] ?? null,
            quantity: (int) ($line['quantity'] ?? 0),
            unitPrice: $this->amount($line['originalUnitPriceSet'] ?? []),
            totalPrice: $this->amount($line['discountedTotalSet'] ?? []),
            rawPayload: $line,
        );
    }

    private function query(): string
    {
        return <<<'GRAPHQL'
query GetDraftOrders($first: Int!, $after: String) {
  draftOrders(first: $first, after: $after, sortKey: UPDATED_AT, reverse: true) {
    edges {
      node {
        id
        name
        status
        invoiceUrl
        currencyCode
        completedAt
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
        totalPriceSet {
          shopMoney {
            amount
            currencyCode
          }
        }
        lineItems(first: 100) {
          edges {
            node {
              ...DraftOrderLineItemFields
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

fragment DraftOrderLineItemFields on DraftOrderLineItem {
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
query GetDraftOrderLineItems($id: ID!, $after: String) {
  node(id: $id) {
    ... on DraftOrder {
      lineItems(first: 100, after: $after) {
        edges {
          node {
            ...DraftOrderLineItemFields
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

fragment DraftOrderLineItemFields on DraftOrderLineItem {
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
