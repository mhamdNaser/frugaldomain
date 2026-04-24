<?php

namespace App\Modules\Shopify\Webhooks\Services;

class ShopifyWebhookTopicCatalog
{
    /**
     * @return array<int, string>
     */
    public function all(): array
    {
        return [
            'APP_UNINSTALLED',
            'APP_SCOPES_UPDATE',
            'SHOP_UPDATE',

            'PRODUCTS_CREATE',
            'PRODUCTS_UPDATE',
            'PRODUCTS_DELETE',

            'COLLECTIONS_CREATE',
            'COLLECTIONS_UPDATE',
            'COLLECTIONS_DELETE',
            'COLLECTION_LISTINGS_ADD',
            'COLLECTION_LISTINGS_UPDATE',
            'COLLECTION_LISTINGS_REMOVE',

            'INVENTORY_LEVELS_UPDATE',
            'INVENTORY_LEVELS_CONNECT',
            'INVENTORY_LEVELS_DISCONNECT',
            'INVENTORY_ITEMS_CREATE',
            'INVENTORY_ITEMS_UPDATE',
            'INVENTORY_ITEMS_DELETE',
            'LOCATIONS_CREATE',
            'LOCATIONS_UPDATE',
            'LOCATIONS_DELETE',
            'LOCATIONS_ACTIVATE',
            'LOCATIONS_DEACTIVATE',

            'ORDERS_CREATE',
            'ORDERS_UPDATED',
            'ORDERS_CANCELLED',
            'ORDERS_DELETE',
            'ORDERS_FULFILLED',
            'ORDERS_PAID',
            'ORDERS_EDITED',
            'ORDERS_RISK_ASSESSMENT_CHANGED',
            'ORDER_TRANSACTIONS_CREATE',
            'REFUNDS_CREATE',

            'DRAFT_ORDERS_CREATE',
            'DRAFT_ORDERS_UPDATE',
            'DRAFT_ORDERS_DELETE',

            'FULFILLMENTS_CREATE',
            'FULFILLMENTS_UPDATE',
            'FULFILLMENT_ORDERS_ORDER_ROUTING_COMPLETE',
            'FULFILLMENT_ORDERS_PLACED_ON_HOLD',
            'FULFILLMENT_ORDERS_RELEASED_HOLD',
            'FULFILLMENT_ORDERS_CANCELLATION_REQUEST_SUBMITTED',
            'FULFILLMENT_ORDERS_CANCELLATION_REQUEST_ACCEPTED',
            'FULFILLMENT_ORDERS_CANCELLATION_REQUEST_REJECTED',
            'FULFILLMENT_ORDERS_FULFILLMENT_REQUEST_SUBMITTED',
            'FULFILLMENT_ORDERS_FULFILLMENT_REQUEST_ACCEPTED',
            'FULFILLMENT_ORDERS_FULFILLMENT_REQUEST_REJECTED',
            'FULFILLMENT_ORDERS_MERGED',
            'FULFILLMENT_ORDERS_SPLIT',
            'FULFILLMENT_ORDERS_MOVED',
            'FULFILLMENT_ORDERS_RESCHEDULED',
            'FULFILLMENT_ORDERS_CANCELLED',
            'FULFILLMENT_HOLDS_ADDED',
            'FULFILLMENT_HOLDS_RELEASED',

            'CUSTOMERS_CREATE',
            'CUSTOMERS_UPDATE',
            'CUSTOMERS_DELETE',
            'CUSTOMERS_DISABLE',
            'CUSTOMERS_ENABLE',
            'CUSTOMERS_MARKETING_CONSENT_UPDATE',
            'CUSTOMERS_EMAIL_MARKETING_CONSENT_UPDATE',
            'CUSTOMER_ACCOUNT_SETTINGS_UPDATE',

            'DISCOUNTS_CREATE',
            'DISCOUNTS_UPDATE',
            'DISCOUNTS_DELETE',

            'PROFILES_CREATE',
            'PROFILES_UPDATE',
            'PROFILES_DELETE',

            'RETURNS_APPROVE',
            'RETURNS_DECLINE',
            'RETURNS_REQUEST',
            'RETURNS_UPDATE',
            'RETURNS_PROCESS',
            'RETURNS_CANCEL',
            'RETURNS_CLOSE',
            'RETURNS_REOPEN',
            'REVERSE_DELIVERIES_ATTACH_DELIVERABLE',

            'MARKETS_CREATE',
            'MARKETS_UPDATE',
            'MARKETS_DELETE',
            'PUBLICATIONS_DELETE',

            'METAFIELD_DEFINITIONS_CREATE',
            'METAFIELD_DEFINITIONS_UPDATE',
            'METAFIELD_DEFINITIONS_DELETE',

            'SELLING_PLAN_GROUPS_CREATE',
            'SELLING_PLAN_GROUPS_UPDATE',
            'SELLING_PLAN_GROUPS_DELETE',

            'THEMES_CREATE',
            'THEMES_UPDATE',
            'THEMES_DELETE',
            'THEMES_PUBLISH',
        ];
    }
}
