<?php

namespace App\Modules\Fulfillment\Repositories\Eloquent;

use App\Modules\Fulfillment\Models\Fulfillment;
use App\Modules\Fulfillment\Repositories\Interfaces\FulfillmentsRepositoryInterface;

class FrontendFulfillmentsRepository implements FulfillmentsRepositoryInterface
{
    public function __construct(
        protected Fulfillment $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $orderId = null)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(Fulfillment::query())
            ->with([
                'order:id,order_number,status',
                'service:id,name,service_name',
            ])
            ->withCount(['items', 'tracking'])
            ->when($orderId, fn ($query) => $query->where('order_id', $orderId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('shopify_fulfillment_id', 'like', "%{$search}%")
                        ->orWhere('shopify_order_id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('shipment_status', 'like', "%{$search}%")
                        ->orWhere('tracking_company', 'like', "%{$search}%")
                        ->orWhere('tracking_number', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('shopify_created_at')
            ->orderByDesc('id')
            ->paginate($rowsPerPage, ['*'], 'page', $page);
    }

    public function find(int $id)
    {
        return $this->applyTenantScope($this->model->newQuery())->findOrFail($id);
    }

    public function findForFrontend(int $id)
    {
        return $this->applyTenantScope(Fulfillment::query())
            ->with([
                'order:id,order_number,status,payment_status,fulfillment_status',
                'service:id,name,service_name,email,type',
                'items:id,fulfillment_id,order_item_id,shopify_line_item_id,quantity',
                'items.orderItem:id,order_id,product_title,sku,quantity',
                'tracking:id,fulfillment_id,company,number,url',
            ])
            ->withCount(['items', 'tracking'])
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $fulfillment = $this->find($id);
        $fulfillment->fill($data);
        $fulfillment->save();

        return $this->findForFrontend($fulfillment->id);
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
