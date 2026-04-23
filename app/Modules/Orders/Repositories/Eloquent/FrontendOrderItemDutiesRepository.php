<?php

namespace App\Modules\Orders\Repositories\Eloquent;

use App\Modules\Orders\Models\OrderItemDuty;
use App\Modules\Orders\Repositories\Interfaces\OrderItemDutiesRepositoryInterface;

class FrontendOrderItemDutiesRepository implements OrderItemDutiesRepositoryInterface
{
    public function __construct(
        protected OrderItemDuty $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $orderDutyId = null, $orderItemId = null)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(OrderItemDuty::query())
            ->with([
                'orderDuty:id,order_id,shopify_duty_id,harmonized_system_code',
                'orderItem:id,order_id,product_title,sku,quantity',
            ])
            ->when($orderDutyId, fn ($query) => $query->where('order_duty_id', $orderDutyId))
            ->when($orderItemId, fn ($query) => $query->where('order_item_id', $orderItemId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('shopify_line_item_id', 'like', "%{$search}%")
                        ->orWhere('shopify_duty_id', 'like', "%{$search}%")
                        ->orWhere('harmonized_system_code', 'like', "%{$search}%")
                        ->orWhere('currency', 'like', "%{$search}%");
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
        return $this->applyTenantScope(OrderItemDuty::query())
            ->with([
                'orderDuty:id,order_id,shopify_duty_id,harmonized_system_code',
                'orderItem:id,order_id,product_title,sku,quantity',
            ])
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $itemDuty = $this->find($id);
        $itemDuty->fill($data);
        $itemDuty->save();

        return $this->findForFrontend($itemDuty->id);
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
