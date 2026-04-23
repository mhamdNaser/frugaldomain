<?php

namespace App\Modules\Orders\Repositories\Eloquent;

use App\Modules\Orders\Models\OrderReturnItem;
use App\Modules\Orders\Repositories\Interfaces\OrderReturnItemsRepositoryInterface;

class FrontendOrderReturnItemsRepository implements OrderReturnItemsRepositoryInterface
{
    public function __construct(
        protected OrderReturnItem $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $orderReturnId = null, $orderItemId = null)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(OrderReturnItem::query())
            ->with([
                'orderReturn:id,order_id,shopify_return_id,status,name',
                'orderItem:id,order_id,product_title,sku,quantity',
            ])
            ->when($orderReturnId, fn ($query) => $query->where('order_return_id', $orderReturnId))
            ->when($orderItemId, fn ($query) => $query->where('order_item_id', $orderItemId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('shopify_return_line_item_id', 'like', "%{$search}%")
                        ->orWhere('shopify_line_item_id', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%");
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
        return $this->applyTenantScope(OrderReturnItem::query())
            ->with([
                'orderReturn:id,order_id,shopify_return_id,status,name',
                'orderItem:id,order_id,product_title,sku,quantity',
            ])
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $returnItem = $this->find($id);
        $returnItem->fill($data);
        $returnItem->save();

        return $this->findForFrontend($returnItem->id);
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
