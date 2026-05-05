<?php

namespace App\Modules\Orders\Services;

use App\Modules\Orders\Models\Order;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Shopify\Support\ShopifyHelper;
use App\Modules\Stores\Models\Store;

class AdminOrderShopifySyncService
{
    public function sync(Store $store, Order $order, bool $sendReceipt = false): array
    {
        $order->loadMissing(['customer', 'taxLines', 'items.variant.product']);

        $response = (new ShopifyClient($store))->query(
            query: $this->mutation(),
            variables: [
                'order' => $this->buildOrderInput($order),
                'options' => [
                    'sendReceipt' => $sendReceipt,
                    'sendFulfillmentReceipt' => false,
                ],
            ],
        );

        $payload = $response['data']['orderCreate'] ?? [];
        $errors = $payload['userErrors'] ?? [];

        if (!empty($errors)) {
            $message = collect($errors)->pluck('message')->filter()->implode(' | ');
            throw new \RuntimeException($message ?: 'Shopify rejected the order.');
        }

        $shopifyOrder = $payload['order'] ?? [];
        $shopifyOrderId = ShopifyHelper::extractId($shopifyOrder['id'] ?? null);

        if ($shopifyOrderId) {
            $order->shopify_order_id = $shopifyOrderId;
            $order->order_number = $shopifyOrder['name'] ?? $order->order_number;
            $order->raw_payload = $shopifyOrder;
            $order->shopify_created_at = now();
            $order->shopify_updated_at = now();
            $order->save();
        }

        $customerId = ShopifyHelper::extractId($shopifyOrder['customer']['id'] ?? null);
        if ($customerId && $order->customer && !$order->customer->shopify_customer_id) {
            $order->customer->shopify_customer_id = $customerId;
            $order->customer->save();
        }

        return $response;
    }

    private function buildOrderInput(Order $order): array
    {
        $currency = strtoupper($order->currency ?: 'USD');

        $input = [
            'email' => $order->email,
            'currency' => $currency,
            'financialStatus' => $this->financialStatus($order->payment_status),
            'fulfillmentStatus' => $this->fulfillmentStatus($order->fulfillment_status),
            'processedAt' => optional($order->placed_at ?: now())->toIso8601String(),
            'note' => data_get($order->raw_payload, 'note'),
            'tags' => data_get($order->raw_payload, 'tags', []),
            'lineItems' => $order->items->map(fn ($item) => [
                'variantId' => 'gid://shopify/ProductVariant/' . $item->shopify_variant_id,
                'quantity' => (int) $item->quantity,
                'sku' => $item->sku,
                'title' => $item->product_title,
                'variantTitle' => $item->variant_title,
                'taxable' => (bool) ($item->variant?->taxable ?? true),
                'priceSet' => [
                    'shopMoney' => [
                        'amount' => (string) $item->unit_price,
                        'currencyCode' => $currency,
                    ],
                ],
            ])->values()->all(),
        ];

        $customer = $order->customer;
        if ($customer?->shopify_customer_id) {
            $input['customer'] = [
                'toAssociate' => [
                    'id' => 'gid://shopify/Customer/' . $customer->shopify_customer_id,
                ],
            ];
        } elseif ($customer?->email || $customer?->phone) {
            $input['customer'] = [
                'toUpsert' => array_filter([
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'firstName' => $customer->first_name,
                    'lastName' => $customer->last_name,
                ]),
            ];
        }

        $shipping = (float) $order->shipping;
        if ($shipping > 0) {
            $input['shippingLines'] = [[
                'title' => 'Manual shipping',
                'priceSet' => $this->moneyBag($shipping, $currency),
            ]];
        }

        $discount = (float) $order->discount;
        if ($discount > 0) {
            $input['discountCode'] = [
                'itemFixedDiscountCode' => [
                    'code' => 'LOCAL_ADMIN_DISCOUNT',
                    'amountSet' => $this->moneyBag($discount, $currency),
                ],
            ];
        }

        $taxLines = $order->taxLines;
        if ($taxLines->isNotEmpty()) {
            $input['taxLines'] = $taxLines->map(fn ($taxLine) => [
                'title' => $taxLine->title ?: 'Manual tax',
                'rate' => (float) $taxLine->rate,
                'priceSet' => $this->moneyBag((float) $taxLine->price, $currency),
            ])->values()->all();
        } else {
            $tax = (float) $order->tax;
            if ($tax > 0) {
                $taxableBase = max(0.01, (float) $order->subtotal - $discount);
                $input['taxLines'] = [[
                    'title' => 'Manual tax',
                    'rate' => round($tax / $taxableBase, 6),
                    'priceSet' => $this->moneyBag($tax, $currency),
                ]];
            }
        }

        return array_filter($input, fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    private function moneyBag(float $amount, string $currency): array
    {
        return [
            'shopMoney' => [
                'amount' => number_format($amount, 2, '.', ''),
                'currencyCode' => $currency,
            ],
        ];
    }

    private function financialStatus(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'paid' => 'PAID',
            'authorized' => 'AUTHORIZED',
            'partially_paid' => 'PARTIALLY_PAID',
            'refunded' => 'REFUNDED',
            'voided' => 'VOIDED',
            default => 'PENDING',
        };
    }

    private function fulfillmentStatus(?string $status): string
    {
        return strtolower((string) $status) === 'fulfilled' ? 'FULFILLED' : 'UNFULFILLED';
    }

    private function mutation(): string
    {
        return <<<'GRAPHQL'
mutation AdminOrderCreate($order: OrderCreateOrderInput!, $options: OrderCreateOptionsInput) {
  orderCreate(order: $order, options: $options) {
    userErrors {
      field
      message
    }
    order {
      id
      name
      email
      createdAt
      displayFinancialStatus
      displayFulfillmentStatus
      customer {
        id
        email
        firstName
        lastName
      }
      totalPriceSet {
        shopMoney {
          amount
          currencyCode
        }
      }
    }
  }
}
GRAPHQL;
    }
}
