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

        if ($this->startsWith($topic, ['orders/'])) {
            return ['orders', 'financials', 'order-risk-channel', 'order-duties'];
        }

        if ($this->startsWith($topic, ['draft_orders/'])) {
            return ['draft-orders'];
        }

        if ($this->startsWith($topic, ['fulfillments/', 'fulfillment_orders/'])) {
            return ['fulfillments'];
        }

        if ($this->startsWith($topic, ['customers/'])) {
            return ['customers', 'customer-marketing-consent'];
        }

        if ($this->startsWith($topic, ['discounts/', 'discount_codes/'])) {
            return ['discounts'];
        }

        if ($this->startsWith($topic, ['blogs/', 'articles/', 'comments/', 'pages/', 'menus/'])) {
            return ['content'];
        }

        if ($this->startsWith($topic, ['files/'])) {
            return ['files'];
        }

        if ($this->startsWith($topic, ['delivery_profiles/', 'shipping_zones/'])) {
            return ['shipping'];
        }

        if ($this->startsWith($topic, ['returns/', 'reverse_deliveries/', 'return_request/'])) {
            return ['returns-exchanges-reverse'];
        }

        if ($this->startsWith($topic, ['inventory_items/', 'locations/'])) {
            return ['inventory-states'];
        }

        if ($this->startsWith($topic, ['metaobject_definitions/', 'metaobjects/'])) {
            return ['metaobject-definitions'];
        }

        if ($this->startsWith($topic, ['selling_plan_groups/', 'subscription_contracts/'])) {
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
