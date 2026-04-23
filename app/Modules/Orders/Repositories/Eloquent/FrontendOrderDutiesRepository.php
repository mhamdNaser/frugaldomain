<?php

namespace App\Modules\Orders\Repositories\Eloquent;

use App\Modules\Orders\Models\OrderDuty;
use App\Modules\Orders\Repositories\Interfaces\OrderDutiesRepositoryInterface;

class FrontendOrderDutiesRepository implements OrderDutiesRepositoryInterface
{
    public function __construct(
        protected OrderDuty $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $orderId = null)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(OrderDuty::query())
            ->with(['order:id,order_number,status,total,currency'])
            ->withCount('itemDuties')
            ->when($orderId, fn ($query) => $query->where('order_id', $orderId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('shopify_order_id', 'like', "%{$search}%")
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
        return $this->applyTenantScope(OrderDuty::query())
            ->with([
                'order:id,order_number,status,total,currency',
                'itemDuties:id,order_duty_id,order_item_id,shopify_line_item_id,amount,currency',
            ])
            ->withCount('itemDuties')
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $orderDuty = $this->find($id);
        $orderDuty->fill($data);
        $orderDuty->save();

        return $this->findForFrontend($orderDuty->id);
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
