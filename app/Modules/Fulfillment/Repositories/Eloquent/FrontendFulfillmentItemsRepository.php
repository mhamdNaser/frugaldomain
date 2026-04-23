<?php

namespace App\Modules\Fulfillment\Repositories\Eloquent;

use App\Modules\Fulfillment\Models\FulfillmentItem;
use App\Modules\Fulfillment\Repositories\Interfaces\FulfillmentItemsRepositoryInterface;

class FrontendFulfillmentItemsRepository implements FulfillmentItemsRepositoryInterface
{
    public function __construct(
        protected FulfillmentItem $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $fulfillmentId = null, $orderItemId = null)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(FulfillmentItem::query())
            ->with([
                'fulfillment:id,order_id,status,tracking_number',
                'orderItem:id,order_id,product_title,sku,quantity',
            ])
            ->when($fulfillmentId, fn ($query) => $query->where('fulfillment_id', $fulfillmentId))
            ->when($orderItemId, fn ($query) => $query->where('order_item_id', $orderItemId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('shopify_line_item_id', 'like', "%{$search}%")
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
        return $this->applyTenantScope(FulfillmentItem::query())
            ->with([
                'fulfillment:id,order_id,status,tracking_number',
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
