<?php

namespace App\Modules\Marketing\Repositories\Eloquent;

use App\Modules\Marketing\Models\DiscountUsage;
use App\Modules\Marketing\Repositories\Interfaces\DiscountUsagesRepositoryInterface;

class FrontendDiscountUsagesRepository implements DiscountUsagesRepositoryInterface
{
    public function __construct(
        protected DiscountUsage $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $discountId = null, $orderId = null)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(DiscountUsage::query())
            ->with([
                'discount:id,title,discount_type,status',
                'order:id,order_number,status,total,currency',
            ])
            ->when($discountId, fn ($query) => $query->where('discount_id', $discountId))
            ->when($orderId, fn ($query) => $query->where('order_id', $orderId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('shopify_order_id', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('currency', 'like', "%{$search}%")
                        ->orWhereHas('discount', function ($discountQuery) use ($search) {
                            $discountQuery->where('title', 'like', "%{$search}%");
                        })
                        ->orWhereHas('order', function ($orderQuery) use ($search) {
                            $orderQuery->where('order_number', 'like', "%{$search}%");
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
        return $this->applyTenantScope(DiscountUsage::query())
            ->with([
                'discount:id,title,discount_type,status',
                'order:id,order_number,status,total,currency',
            ])
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $usage = $this->find($id);
        $usage->fill($data);
        $usage->save();

        return $this->findForFrontend($usage->id);
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
