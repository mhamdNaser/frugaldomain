<?php

namespace App\Modules\Fulfillment\Repositories\Eloquent;

use App\Modules\Fulfillment\Models\FulfillmentOrderItem;
use App\Modules\Fulfillment\Repositories\Interfaces\FulfillmentOrderItemsRepositoryInterface;

class FrontendFulfillmentOrderItemsRepository implements FulfillmentOrderItemsRepositoryInterface
{
    public function __construct(
        protected FulfillmentOrderItem $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $fulfillmentOrderId = null, $orderItemId = null)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(FulfillmentOrderItem::query())
            ->with([
                'fulfillmentOrder:id,order_id,status,request_status,shopify_fulfillment_order_id',
                'orderItem:id,order_id,product_title,sku,quantity',
            ])
            ->when($fulfillmentOrderId, fn ($query) => $query->where('fulfillment_order_id', $fulfillmentOrderId))
            ->when($orderItemId, fn ($query) => $query->where('order_item_id', $orderItemId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('shopify_fulfillment_order_line_item_id', 'like', "%{$search}%")
                        ->orWhere('shopify_line_item_id', 'like', "%{$search}%")
                        ->orWhereHas('orderItem', function ($orderItemQuery) use ($search) {
                            $orderItemQuery->where('product_title', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate($rowsPerPage, ['*'], 'page', $page);
    }

    public function find(int $id)
    {
        return $this->applyTenantScope($this->model->newQuery())->findOrFail($id);
    }

    public function findForFrontend(int $id)
    {
        return $this->applyTenantScope(FulfillmentOrderItem::query())
            ->with([
                'fulfillmentOrder:id,order_id,status,request_status,shopify_fulfillment_order_id',
                'orderItem:id,order_id,product_title,sku,quantity',
            ])
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $item = $this->find($id);
        $item->fill($data);
        $item->save();

        return $this->findForFrontend($item->id);
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
