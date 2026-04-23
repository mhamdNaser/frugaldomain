<?php

namespace App\Modules\Billing\Repositories\Eloquent;

use App\Modules\Billing\Models\RefundItem;
use App\Modules\Billing\Repositories\Interfaces\RefundItemsRepositoryInterface;

class FrontendRefundItemsRepository implements RefundItemsRepositoryInterface
{
    public function __construct(
        protected RefundItem $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $refundId = null)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(RefundItem::query())
            ->with(['refund:id,shopify_refund_id,currency,total', 'orderItem:id,product_title,sku'])
            ->when($refundId, fn ($query) => $query->where('refund_id', $refundId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('restock_type', 'like', "%{$search}%")
                        ->orWhere('currency', 'like', "%{$search}%")
                        ->orWhere('shopify_line_item_id', 'like', "%{$search}%");
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
        return $this->applyTenantScope(RefundItem::query())
            ->with(['refund:id,shopify_refund_id,currency,total', 'orderItem:id,product_title,variant_title,sku'])
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
