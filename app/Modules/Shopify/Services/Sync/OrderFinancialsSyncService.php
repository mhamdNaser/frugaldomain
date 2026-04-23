<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Billing\Models\PaymentTransaction;
use App\Modules\Billing\Models\Refund;
use App\Modules\Billing\Models\RefundItem;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Shopify\DTOs\RefundData;
use App\Modules\Shopify\DTOs\RefundItemData;
use App\Modules\Shopify\DTOs\TransactionData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;

class OrderFinancialsSyncService
{
    public function sync(Store $store): int
    {
        $client = new ShopifyClient($store);
        $count = 0;

        Order::query()
            ->where('store_id', $store->id)
            ->whereNotNull('shopify_order_id')
            ->orderBy('id')
            ->chunkById(50, function ($orders) use ($store, $client, &$count) {
                foreach ($orders as $order) {
                    if ($this->syncOrderFinancials($store, $order, $client)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    private function syncOrderFinancials(Store $store, Order $order, ShopifyClient $client): bool
    {
        $response = $client->query(
            query: $this->query(),
            variables: [
                'id' => $order->shopify_order_id,
            ],
        );

        $node = $response['data']['node'] ?? null;

        if (!is_array($node)) {
            return false;
        }

        foreach ($node['transactions'] ?? [] as $transaction) {
            if (is_array($transaction)) {
                $this->syncTransaction($store, $order, $this->transactionData($transaction, $order->currency));
            }
        }

        foreach ($node['refunds'] ?? [] as $refundNode) {
            if (is_array($refundNode)) {
                $refundData = $this->refundData($refundNode, $order->currency);
                $refund = $this->syncRefund($store, $order, $refundData);

                foreach ($refundData->items as $itemData) {
                    if ($itemData instanceof RefundItemData) {
                        $this->syncRefundItem($store, $refund, $itemData);
                    }
                }

                foreach ($refundData->transactions as $transactionData) {
                    if ($transactionData instanceof TransactionData) {
                        $this->syncTransaction($store, $order, $transactionData, $refund);
                    }
                }
            }
        }

        return true;
    }

    private function syncTransaction(Store $store, Order $order, TransactionData $data, ?Refund $refund = null): void
    {
        PaymentTransaction::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_transaction_id' => $data->shopifyTransactionId,
            ],
            [
                'order_id' => $order->id,
                'refund_id' => $refund?->id,
                'parent_shopify_transaction_id' => $data->parentShopifyTransactionId,
                'gateway' => $data->gateway,
                'account_number' => $data->accountNumber,
                'transaction_reference' => $data->transactionReference,
                'kind' => $data->kind,
                'amount' => $data->amount,
                'currency' => $data->currency ?? $order->currency ?? 'USD',
                'status' => $data->status,
                'test' => $data->test,
                'manual_payment_gateway' => $data->manualPaymentGateway,
                'processed_at' => $data->processedAt,
                'raw_response' => json_encode($data->rawPayload),
            ],
        );
    }

    private function syncRefund(Store $store, Order $order, RefundData $data): Refund
    {
        return Refund::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_refund_id' => $data->shopifyRefundId,
            ],
            [
                'order_id' => $order->id,
                'note' => $data->note,
                'total' => $data->total,
                'currency' => $data->currency ?? $order->currency,
                'raw_payload' => $data->rawPayload,
                'processed_at' => $data->processedAt,
                'shopify_created_at' => $data->shopifyCreatedAt,
                'shopify_updated_at' => $data->shopifyUpdatedAt,
            ],
        );
    }

    private function syncRefundItem(Store $store, Refund $refund, RefundItemData $data): void
    {
        RefundItem::query()->updateOrCreate(
            [
                'refund_id' => $refund->id,
                'shopify_refund_line_item_id' => $data->shopifyRefundLineItemId,
            ],
            [
                'store_id' => $store->id,
                'order_item_id' => $this->orderItemId($store, $data->shopifyLineItemId),
                'shopify_line_item_id' => $data->shopifyLineItemId,
                'quantity' => $data->quantity,
                'restock_type' => $data->restockType,
                'restocked' => $data->restocked,
                'subtotal' => $data->subtotal,
                'tax' => $data->tax,
                'total' => $data->total,
                'currency' => $data->currency,
                'raw_payload' => $data->rawPayload,
            ],
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

    private function amount(array $moneySet): float
    {
        return (float) ($moneySet['shopMoney']['amount'] ?? 0);
    }

    private function currency(array $moneySet): ?string
    {
        return $moneySet['shopMoney']['currencyCode'] ?? null;
    }

    private function transactionData(array $node, ?string $fallbackCurrency = null): TransactionData
    {
        return new TransactionData(
            shopifyTransactionId: $node['id'],
            parentShopifyTransactionId: $node['parentTransaction']['id'] ?? null,
            gateway: $node['gateway'] ?? $node['formattedGateway'] ?? 'unknown',
            accountNumber: $node['accountNumber'] ?? null,
            transactionReference: $node['paymentId'] ?? $node['id'] ?? 'unknown',
            kind: strtolower((string) ($node['kind'] ?? '')),
            amount: $this->amount($node['amountSet'] ?? []),
            currency: $this->currency($node['amountSet'] ?? []) ?? $fallbackCurrency,
            status: strtolower((string) ($node['status'] ?? 'pending')),
            test: (bool) ($node['test'] ?? false),
            manualPaymentGateway: (bool) ($node['manualPaymentGateway'] ?? false),
            processedAt: $node['processedAt'] ?? $node['createdAt'] ?? null,
            rawPayload: $node,
        );
    }

    private function refundData(array $node, ?string $fallbackCurrency = null): RefundData
    {
        return new RefundData(
            shopifyRefundId: $node['id'],
            note: $node['note'] ?? null,
            total: $this->amount($node['totalRefundedSet'] ?? []),
            currency: $this->currency($node['totalRefundedSet'] ?? []) ?? $fallbackCurrency,
            processedAt: $node['processedAt'] ?? null,
            shopifyCreatedAt: $node['createdAt'] ?? null,
            shopifyUpdatedAt: $node['updatedAt'] ?? null,
            rawPayload: $node,
            items: array_values(array_filter(array_map(
                fn (array $edge): ?RefundItemData => $this->refundItemData($edge['node'] ?? null),
                $node['refundLineItems']['edges'] ?? [],
            ))),
            transactions: array_values(array_filter(array_map(
                fn (array $edge): ?TransactionData => is_array($edge['node'] ?? null)
                    ? $this->transactionData($edge['node'], $fallbackCurrency)
                    : null,
                $node['transactions']['edges'] ?? [],
            ))),
        );
    }

    private function refundItemData(mixed $node): ?RefundItemData
    {
        if (!is_array($node)) {
            return null;
        }

        return new RefundItemData(
            shopifyRefundLineItemId: $node['id'] ?? null,
            shopifyLineItemId: $node['lineItem']['id'] ?? null,
            quantity: (int) ($node['quantity'] ?? 0),
            restockType: strtolower((string) ($node['restockType'] ?? '')),
            restocked: (bool) ($node['restocked'] ?? false),
            subtotal: $this->amount($node['subtotalSet'] ?? []),
            tax: $this->amount($node['totalTaxSet'] ?? []),
            total: $this->amount($node['priceSet'] ?? []),
            currency: $this->currency($node['subtotalSet'] ?? []),
            rawPayload: $node,
        );
    }

    private function query(): string
    {
        return <<<'GRAPHQL'
query GetOrderFinancials($id: ID!) {
  node(id: $id) {
    ... on Order {
      id
      transactions(first: 100) {
        ...OrderTransactionFields
      }
      refunds(first: 100) {
        id
        note
        processedAt
        createdAt
        updatedAt
        totalRefundedSet {
          shopMoney {
            amount
            currencyCode
          }
        }
        refundLineItems(first: 100) {
          edges {
            node {
              id
              quantity
              restockType
              restocked
              lineItem {
                id
              }
              priceSet {
                shopMoney {
                  amount
                  currencyCode
                }
              }
              subtotalSet {
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
            }
          }
        }
        transactions(first: 100) {
          edges {
            node {
              ...OrderTransactionFields
            }
          }
        }
      }
    }
  }
}

fragment OrderTransactionFields on OrderTransaction {
  id
  accountNumber
  formattedGateway
  gateway
  kind
  manualPaymentGateway
  parentTransaction {
    id
  }
  paymentId
  processedAt
  createdAt
  status
  test
  amountSet {
    shopMoney {
      amount
      currencyCode
    }
  }
}
GRAPHQL;
    }
}
