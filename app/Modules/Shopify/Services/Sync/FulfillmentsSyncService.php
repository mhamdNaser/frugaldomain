<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Fulfillment\Models\Fulfillment;
use App\Modules\Fulfillment\Models\FulfillmentItem;
use App\Modules\Fulfillment\Models\FulfillmentOrder;
use App\Modules\Fulfillment\Models\FulfillmentOrderItem;
use App\Modules\Fulfillment\Models\FulfillmentService;
use App\Modules\Fulfillment\Models\FulfillmentTracking;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Shopify\DTOs\FulfillmentData;
use App\Modules\Shopify\DTOs\FulfillmentItemData;
use App\Modules\Shopify\DTOs\FulfillmentOrderData;
use App\Modules\Shopify\DTOs\FulfillmentOrderItemData;
use App\Modules\Shopify\DTOs\FulfillmentServiceData;
use App\Modules\Shopify\DTOs\FulfillmentTrackingData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;

class FulfillmentsSyncService
{
    private const PAGE_SIZE = 50;

    public function sync(Store $store): void
    {
        $client = new ShopifyClient($store);

        $this->syncFulfillmentServices($store, $client);

        Order::query()
            ->where('store_id', $store->id)
            ->whereNotNull('shopify_order_id')
            ->chunkById(50, function ($orders) use ($store, $client) {
                foreach ($orders as $order) {
                    $this->syncOrderFulfillmentData($store, $client, $order);
                }
            });
    }

    private function syncFulfillmentServices(Store $store, ShopifyClient $client): void
    {
        $response = $client->query(query: $this->fulfillmentServicesQuery());

        $services = $response['data']['shop']['fulfillmentServices'] ?? null;

        if (!is_array($services)) {
            return;
        }

        foreach ($services as $node) {
            if (is_array($node) && !empty($node['id'])) {
                $this->upsertFulfillmentService($store, $this->fulfillmentServiceData($node));
            }
        }
    }

    private function syncOrderFulfillmentData(Store $store, ShopifyClient $client, Order $order): void
    {
        $response = $client->query(
            query: $this->orderFulfillmentDataQuery(),
            variables: ['id' => $order->shopify_order_id],
        );

        $node = $response['data']['node'] ?? null;

        if (!is_array($node)) {
            return;
        }

        foreach ($node['fulfillmentOrders']['edges'] ?? [] as $edge) {
            $fulfillmentOrder = $edge['node'] ?? null;

            if (is_array($fulfillmentOrder)) {
                $this->upsertFulfillmentOrder($store, $order, $this->fulfillmentOrderData($fulfillmentOrder));
            }
        }

        foreach ($node['fulfillments'] ?? [] as $fulfillment) {
            if (is_array($fulfillment)) {
                $this->upsertFulfillment($store, $order, $this->fulfillmentData($fulfillment));
            }
        }
    }

    private function upsertFulfillmentService(Store $store, FulfillmentServiceData $data): FulfillmentService
    {
        return FulfillmentService::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_fulfillment_service_id' => $data->shopifyFulfillmentServiceId,
            ],
            [
                'name' => $data->name,
                'email' => $data->email,
                'service_name' => $data->serviceName,
                'type' => $data->type,
                'callback_url' => $data->callbackUrl,
                'raw_payload' => $data->rawPayload,
            ]
        );
    }

    private function upsertFulfillmentOrder(Store $store, Order $order, FulfillmentOrderData $data): FulfillmentOrder
    {
        $service = $data->service ? $this->upsertFulfillmentService($store, $data->service) : null;

        $fulfillmentOrder = FulfillmentOrder::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_fulfillment_order_id' => $data->shopifyFulfillmentOrderId,
            ],
            [
                'order_id' => $order->id,
                'fulfillment_service_id' => $service?->id,
                'shopify_order_id' => $order->shopify_order_id,
                'shopify_assigned_location_id' => $data->shopifyAssignedLocationId,
                'assigned_location_name' => $data->assignedLocationName,
                'status' => $data->status,
                'request_status' => $data->requestStatus,
                'fulfill_at' => $data->fulfillAt,
                'fulfill_by' => $data->fulfillBy,
                'destination' => $data->destination,
                'delivery_method' => $data->deliveryMethod,
                'raw_payload' => $data->rawPayload,
                'shopify_created_at' => $data->shopifyCreatedAt,
                'shopify_updated_at' => $data->shopifyUpdatedAt,
            ]
        );

        foreach ($data->items as $itemData) {
            if ($itemData instanceof FulfillmentOrderItemData) {
                $this->upsertFulfillmentOrderItem($store, $fulfillmentOrder, $itemData);
            }
        }

        return $fulfillmentOrder;
    }

    private function upsertFulfillmentOrderItem(Store $store, FulfillmentOrder $fulfillmentOrder, FulfillmentOrderItemData $data): void
    {
        FulfillmentOrderItem::query()->updateOrCreate(
            [
                'fulfillment_order_id' => $fulfillmentOrder->id,
                'shopify_fulfillment_order_line_item_id' => $data->shopifyFulfillmentOrderLineItemId,
            ],
            [
                'store_id' => $store->id,
                'order_item_id' => $this->orderItemId($store, $data->shopifyLineItemId),
                'shopify_line_item_id' => $data->shopifyLineItemId,
                'total_quantity' => $data->totalQuantity,
                'remaining_quantity' => $data->remainingQuantity,
                'raw_payload' => $data->rawPayload,
            ]
        );
    }

    private function upsertFulfillment(Store $store, Order $order, FulfillmentData $data): Fulfillment
    {
        $service = $data->service ? $this->upsertFulfillmentService($store, $data->service) : null;
        $tracking = $data->primaryTracking();

        $fulfillment = Fulfillment::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_fulfillment_id' => $data->shopifyFulfillmentId,
            ],
            [
                'order_id' => $order->id,
                'fulfillment_service_id' => $service?->id,
                'shopify_order_id' => $order->shopify_order_id,
                'name' => $data->name,
                'status' => $data->status,
                'shipment_status' => $data->displayStatus ?? $data->status,
                'tracking_company' => $tracking?->company,
                'tracking_number' => $tracking?->number,
                'tracking_url' => $tracking?->url,
                'raw_payload' => $data->rawPayload,
                'shopify_created_at' => $data->shopifyCreatedAt,
                'shopify_updated_at' => $data->shopifyUpdatedAt,
            ]
        );

        foreach ($data->items as $itemData) {
            if ($itemData instanceof FulfillmentItemData) {
                $this->upsertFulfillmentItem($store, $fulfillment, $itemData);
            }
        }

        foreach ($data->tracking as $trackingData) {
            if ($trackingData instanceof FulfillmentTrackingData) {
                $this->upsertTracking($store, $fulfillment, $trackingData);
            }
        }

        return $fulfillment;
    }

    private function upsertFulfillmentItem(Store $store, Fulfillment $fulfillment, FulfillmentItemData $data): void
    {
        FulfillmentItem::query()->updateOrCreate(
            [
                'fulfillment_id' => $fulfillment->id,
                'shopify_line_item_id' => $data->shopifyLineItemId,
            ],
            [
                'store_id' => $store->id,
                'order_item_id' => $this->orderItemId($store, $data->shopifyLineItemId),
                'quantity' => $data->quantity,
                'raw_payload' => $data->rawPayload,
            ]
        );
    }

    private function upsertTracking(Store $store, Fulfillment $fulfillment, FulfillmentTrackingData $data): void
    {
        FulfillmentTracking::query()->updateOrCreate(
            [
                'fulfillment_id' => $fulfillment->id,
                'number' => $data->number,
                'url' => $data->url,
            ],
            [
                'store_id' => $store->id,
                'company' => $data->company,
                'raw_payload' => $data->rawPayload,
            ]
        );
    }

    private function fulfillmentServiceData(?array $node): ?FulfillmentServiceData
    {
        if (!$node || empty($node['id'])) {
            return null;
        }

        return new FulfillmentServiceData(
            shopifyFulfillmentServiceId: $node['id'],
            name: $node['name'] ?? $node['serviceName'] ?? null,
            email: $node['email'] ?? null,
            serviceName: $node['serviceName'] ?? null,
            type: $node['type'] ?? null,
            callbackUrl: (bool) ($node['callbackUrl'] ?? false),
            rawPayload: $node,
        );
    }

    private function fulfillmentOrderData(array $node): FulfillmentOrderData
    {
        return new FulfillmentOrderData(
            shopifyFulfillmentOrderId: $node['id'],
            service: $this->fulfillmentServiceData($node['assignedLocation']['location']['fulfillmentService'] ?? null),
            shopifyAssignedLocationId: $node['assignedLocation']['location']['id'] ?? null,
            assignedLocationName: $node['assignedLocation']['name'] ?? null,
            status: $node['status'] ?? null,
            requestStatus: $node['requestStatus'] ?? null,
            fulfillAt: $node['fulfillAt'] ?? null,
            fulfillBy: $node['fulfillBy'] ?? null,
            destination: $node['destination'] ?? null,
            deliveryMethod: $node['deliveryMethod'] ?? null,
            shopifyCreatedAt: $node['createdAt'] ?? null,
            shopifyUpdatedAt: $node['updatedAt'] ?? null,
            rawPayload: $node,
            items: array_values(array_filter(array_map(
                fn (array $edge): ?FulfillmentOrderItemData => $this->fulfillmentOrderItemData($edge['node'] ?? null),
                $node['lineItems']['edges'] ?? [],
            ))),
        );
    }

    private function fulfillmentOrderItemData(mixed $node): ?FulfillmentOrderItemData
    {
        if (!is_array($node) || empty($node['id'])) {
            return null;
        }

        return new FulfillmentOrderItemData(
            shopifyFulfillmentOrderLineItemId: $node['id'],
            shopifyLineItemId: $node['lineItem']['id'] ?? null,
            totalQuantity: (int) ($node['totalQuantity'] ?? 0),
            remainingQuantity: (int) ($node['remainingQuantity'] ?? 0),
            rawPayload: $node,
        );
    }

    private function fulfillmentData(array $node): FulfillmentData
    {
        return new FulfillmentData(
            shopifyFulfillmentId: $node['id'],
            service: $this->fulfillmentServiceData($node['service'] ?? null),
            name: $node['name'] ?? null,
            status: $node['status'] ?? null,
            displayStatus: $node['displayStatus'] ?? null,
            shopifyCreatedAt: $node['createdAt'] ?? null,
            shopifyUpdatedAt: $node['updatedAt'] ?? null,
            rawPayload: $node,
            items: array_values(array_filter(array_map(
                fn (array $edge): ?FulfillmentItemData => $this->fulfillmentItemData($edge['node'] ?? null),
                $node['fulfillmentLineItems']['edges'] ?? [],
            ))),
            tracking: array_values(array_filter(array_map(
                fn (array $tracking): ?FulfillmentTrackingData => $this->fulfillmentTrackingData($tracking),
                $node['trackingInfo'] ?? [],
            ))),
        );
    }

    private function fulfillmentItemData(mixed $node): ?FulfillmentItemData
    {
        if (!is_array($node)) {
            return null;
        }

        return new FulfillmentItemData(
            shopifyLineItemId: $node['lineItem']['id'] ?? null,
            quantity: (int) ($node['quantity'] ?? 0),
            rawPayload: $node,
        );
    }

    private function fulfillmentTrackingData(array $node): ?FulfillmentTrackingData
    {
        if (empty($node['number']) && empty($node['url'])) {
            return null;
        }

        return new FulfillmentTrackingData(
            company: $node['company'] ?? null,
            number: $node['number'] ?? null,
            url: $node['url'] ?? null,
            rawPayload: $node,
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

    private function fulfillmentServicesQuery(): string
    {
        return <<<'GRAPHQL'
query GetFulfillmentServices {
  shop {
    fulfillmentServices {
      id
      serviceName
      type
      callbackUrl
    }
  }
}
GRAPHQL;
    }

    private function orderFulfillmentDataQuery(): string
    {
        return <<<'GRAPHQL'
query GetOrderFulfillmentData($id: ID!) {
  node(id: $id) {
    ... on Order {
      fulfillmentOrders(first: 50) {
        edges {
          node {
            id
            status
            requestStatus
            fulfillAt
            fulfillBy
            createdAt
            updatedAt
            assignedLocation {
              name
              location {
                id
                fulfillmentService {
                  id
                  serviceName
                  type
                  callbackUrl
                }
              }
            }
            destination {
              firstName
              lastName
              address1
              address2
              city
              province
              countryCode
              zip
              phone
              email
            }
            deliveryMethod {
              id
              methodType
            }
            lineItems(first: 100) {
              edges {
                node {
                  id
                  totalQuantity
                  remainingQuantity
                  lineItem {
                    id
                  }
                }
              }
            }
          }
        }
      }
      fulfillments(first: 50) {
        id
        name
        status
        displayStatus
        createdAt
        updatedAt
        service {
          id
          serviceName
          type
          callbackUrl
        }
        trackingInfo {
          company
          number
          url
        }
        fulfillmentLineItems(first: 100) {
          edges {
            node {
              quantity
              lineItem {
                id
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
