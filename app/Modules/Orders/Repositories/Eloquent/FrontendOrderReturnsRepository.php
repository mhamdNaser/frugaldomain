<?php

namespace App\Modules\Orders\Repositories\Eloquent;

use App\Modules\Orders\Models\OrderReturn;
use App\Modules\Orders\Repositories\Interfaces\OrderReturnsRepositoryInterface;

class FrontendOrderReturnsRepository implements OrderReturnsRepositoryInterface
{
    public function __construct(
        protected OrderReturn $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $orderId = null)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(OrderReturn::query())
            ->with(['order:id,order_number,status,total,currency'])
            ->withCount('items')
            ->when($orderId, fn ($query) => $query->where('order_id', $orderId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('shopify_return_id', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->paginate($rowsPerPage, ['*'], 'page', $page);
    }

    public function find(int $id)
    {
        return $this->applyTenantScope($this->model->newQuery())->findOrFail($id);
    }

    public function findForFrontend(int $id)
    {
        return $this->applyTenantScope(OrderReturn::query())
            ->with([
                'order:id,order_number,status,total,currency',
                'items:id,order_return_id,order_item_id,shopify_return_line_item_id,quantity,reason',
            ])
            ->withCount('items')
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $orderReturn = $this->find($id);
        $orderReturn->fill($data);
        $orderReturn->save();

        return $this->findForFrontend($orderReturn->id);
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
