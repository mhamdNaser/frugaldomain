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
            'INVENTORY_ITEMS_CREATE',
            'INVENTORY_ITEMS_UPDATE',
            'INVENTORY_ITEMS_DELETE',
            'LOCATIONS_CREATE',
            'LOCATIONS_UPDATE',
            'LOCATIONS_DELETE',

            'ORDERS_CREATE',
            'ORDERS_UPDATED',
            'ORDERS_CANCELLED',
            'ORDERS_DELETE',
            'ORDERS_FULFILLED',
            'ORDERS_PAID',

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

            'CUSTOMERS_CREATE',
            'CUSTOMERS_UPDATE',
            'CUSTOMERS_DELETE',
            'CUSTOMERS_DISABLE',
            'CUSTOMERS_ENABLE',
            'CUSTOMERS_MARKETING_CONSENT_UPDATE',

            'DISCOUNTS_CREATE',
            'DISCOUNTS_UPDATE',
            'DISCOUNTS_DELETE',

            'PAGES_CREATE',
            'PAGES_UPDATE',
            'PAGES_DELETE',
            'BLOGS_CREATE',
            'BLOGS_UPDATE',
            'BLOGS_DELETE',
            'ARTICLES_CREATE',
            'ARTICLES_UPDATE',
            'ARTICLES_DELETE',
            'COMMENTS_CREATE',
            'COMMENTS_UPDATE',
            'COMMENTS_DELETE',
            'MENUS_CREATE',
            'MENUS_UPDATE',
            'MENUS_DELETE',

            'FILES_CREATE',
            'FILES_UPDATE',
            'FILES_DELETE',

            'DELIVERY_PROFILES_CREATE',
            'DELIVERY_PROFILES_UPDATE',
            'DELIVERY_PROFILES_DELETE',

            'RETURNS_APPROVE',
            'RETURNS_DECLINE',
            'RETURNS_REQUEST',

            'ORDER_TRANSACTIONS_CREATE',
            'REFUNDS_CREATE',

            'ORDER_RISKS_CREATE',
            'ORDER_RISKS_UPDATE',

            'MARKETS_CREATE',
            'MARKETS_UPDATE',
            'MARKETS_DELETE',
            'PRICE_LISTS_CREATE',
            'PRICE_LISTS_UPDATE',
            'PRICE_LISTS_DELETE',
            'PUBLICATIONS_CREATE',
            'PUBLICATIONS_UPDATE',
            'PUBLICATIONS_DELETE',

            'METAOBJECT_DEFINITIONS_CREATE',
            'METAOBJECT_DEFINITIONS_UPDATE',
            'METAOBJECT_DEFINITIONS_DELETE',
            'METAOBJECTS_CREATE',
            'METAOBJECTS_UPDATE',
            'METAOBJECTS_DELETE',

            'SELLING_PLAN_GROUPS_CREATE',
            'SELLING_PLAN_GROUPS_UPDATE',
            'SELLING_PLAN_GROUPS_DELETE',
        ];
    }
}

