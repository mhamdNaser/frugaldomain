<?php

namespace App\Modules\Shopify\Webhooks\Services;

class ShopifyWebhookTopicSyncResolver
{
    /**
     * @return array<int, string>
     */
    public function resolve(string $topic): array
    {
        $topic = strtolower(trim($topic));

        if ($topic === '') {
            return [];
        }

        if ($this->startsWith($topic, ['orders/', 'order/'])) {
            return ['orders', 'financials', 'order-risk-channel', 'order-duties'];
        }

        if ($this->startsWith($topic, ['order_transactions/', 'refunds/'])) {
            return ['financials'];
        }

        if ($this->startsWith($topic, ['draft_orders/'])) {
            return ['draft-orders'];
        }

        if ($this->startsWith($topic, ['fulfillments/', 'fulfillment_orders/', 'fulfillment_holds/', 'fulfillment_events/'])) {
            return ['fulfillments'];
        }

        if ($this->startsWith($topic, ['customers/', 'customer/'])) {
            return ['customers', 'customer-marketing-consent'];
        }

        if ($this->startsWith($topic, ['customers_marketing_consent/', 'customers_email_marketing_consent/', 'customer_account_settings/'])) {
            return ['customer-marketing-consent', 'customers'];
        }

        if ($this->startsWith($topic, ['discounts/'])) {
            return ['discounts'];
        }

        if ($this->startsWith($topic, ['blogs/', 'articles/', 'comments/', 'pages/', 'menus/'])) {
            return ['content'];
        }

        if ($this->startsWith($topic, ['files/'])) {
            return ['files'];
        }

        if ($this->startsWith($topic, ['products/', 'product/'])) {
            return ['product-advanced-media'];
        }

        if ($this->startsWith($topic, ['variants/'])) {
            return ['inventory-states', 'product-advanced-media'];
        }

        // Shipping profiles topic is "profiles/*" in current Shopify topics.
        if ($this->startsWith($topic, ['profiles/', 'delivery_profiles/'])) {
            return ['shipping'];
        }

        if ($this->startsWith($topic, ['returns/', 'reverse_deliveries/', 'reverse_fulfillment_orders/'])) {
            return ['returns-exchanges-reverse'];
        }

        if ($this->startsWith($topic, ['inventory_levels/', 'inventory_items/', 'locations/'])) {
            return ['inventory-states'];
        }

        if ($this->startsWith($topic, ['metaobject_definitions/', 'metafield_definitions/', 'metaobjects/'])) {
            return ['metaobject-definitions'];
        }

        if ($this->startsWith($topic, ['selling_plan_groups/', 'subscription_contracts/', 'subscription_billing_'])) {
            return ['selling-plans'];
        }

        if ($this->startsWith($topic, ['markets/', 'price_lists/', 'publications/'])) {
            return ['markets-price-lists'];
        }

        if ($this->startsWith($topic, ['themes/'])) {
            return ['themes'];
        }

        if ($this->startsWith($topic, ['webhook_subscriptions/'])) {
            return ['webhook-subscriptions'];
        }

        return match ($topic) {
            'shop/update' => ['shop-details', 'store-installs'],
            'app/uninstalled' => ['store-installs'],
            default => [],
        };
    }

    /**
     * @param array<int, string> $prefixes
     */
    private function startsWith(string $value, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
