<?php

namespace App\Modules\Fulfillment\Repositories\Eloquent;

use App\Modules\Fulfillment\Models\FulfillmentOrder;
use App\Modules\Fulfillment\Repositories\Interfaces\FulfillmentOrdersRepositoryInterface;

class FrontendFulfillmentOrdersRepository implements FulfillmentOrdersRepositoryInterface
{
    public function __construct(
        protected FulfillmentOrder $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $orderId = null, $fulfillmentServiceId = null)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(FulfillmentOrder::query())
            ->with([
                'order:id,order_number,status',
                'service:id,name,service_name',
            ])
            ->withCount('items')
            ->when($orderId, fn ($query) => $query->where('order_id', $orderId))
            ->when($fulfillmentServiceId, fn ($query) => $query->where('fulfillment_service_id', $fulfillmentServiceId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('shopify_fulfillment_order_id', 'like', "%{$search}%")
                        ->orWhere('shopify_order_id', 'like', "%{$search}%")
                        ->orWhere('assigned_location_name', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('request_status', 'like', "%{$search}%")
                        ->orWhereHas('service', function ($serviceQuery) use ($search) {
                            $serviceQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('service_name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('fulfill_at')
            ->orderByDesc('id')
            ->paginate($rowsPerPage, ['*'], 'page', $page);
    }

    public function find(int $id)
    {
        return $this->applyTenantScope($this->model->newQuery())->findOrFail($id);
    }

    public function findForFrontend(int $id)
    {
        return $this->applyTenantScope(FulfillmentOrder::query())
            ->with([
                'order:id,order_number,status,payment_status,fulfillment_status',
                'service:id,name,service_name,email,type',
                'items:id,fulfillment_order_id,order_item_id,shopify_line_item_id,total_quantity,remaining_quantity',
                'items.orderItem:id,order_id,product_title,sku,quantity',
            ])
            ->withCount('items')
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $fulfillmentOrder = $this->find($id);
        $fulfillmentOrder->fill($data);
        $fulfillmentOrder->save();

        return $this->findForFrontend($fulfillmentOrder->id);
    }

    private function applyTenantScope($query)
    {
        $user = auth()->user();

        if (
            $user
            && method_exists($user, 'hasRole')
            && $user->hasRole('partner')
            && !$user->hasRole('admin')
        ) {
            $storeId = $user->store?->id;
            abort_if(!$storeId, 404, 'No store is linked to the authenticated user.');
            $query->where('store_id', $storeId);
        }

        return $query;
    }
}
