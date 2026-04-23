<?php

namespace App\Modules\Orders\Repositories\Eloquent;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Repositories\Interfaces\OrdersRepositoryInterface;

class FrontendOrdersRepository implements OrdersRepositoryInterface
{
    public function __construct(
        protected Order $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $customerId = null)
    {

        return $this->applyTenantScope(Order::query())
            ->with([
                'customer:id,display_name,email',
                'channel:id,order_id,source_name,channel_name,app_title',
                'latestRisk:id,order_id,risk_level,recommendation,provider,assessed_at',
            ])
            ->withCount('items')
            ->when($customerId, fn ($query) => $query->where('customer_id', $customerId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('order_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('payment_status', 'like', "%{$search}%")
                        ->orWhere('fulfillment_status', 'like', "%{$search}%")
                        ->orWhere('currency', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->paginate($rowsPerPage, ['*'], 'page', $page);
    }

    public function find(int $id)
    {
        return $this->applyTenantScope($this->model->newQuery())->findOrFail($id);
    }

    public function findForFrontend(int $id)
    {
        return $this->applyTenantScope(Order::query())
            ->with([
                'customer:id,display_name,email,phone',
                'items.variant.files',
                'items.variant.optionValues.option',
                'items.variant.product:id,title,handle,featured_image',
                'channel:id,order_id,shopify_order_id,source_name,source_identifier,channel_id,channel_name,app_id,app_title',
                'risks:id,order_id,assessment_id,recommendation,risk_level,provider,assessed_at,facts',
            ])
            ->withCount('items')
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $order = $this->find($id);
        $order->fill($data);
        $order->save();

        return $this->findForFrontend($order->id);
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
