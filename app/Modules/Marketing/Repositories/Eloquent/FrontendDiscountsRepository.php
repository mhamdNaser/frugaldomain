<?php

namespace App\Modules\Marketing\Repositories\Eloquent;

use App\Modules\Marketing\Models\Discount;
use App\Modules\Marketing\Repositories\Interfaces\DiscountsRepositoryInterface;

class FrontendDiscountsRepository implements DiscountsRepositoryInterface
{
    public function __construct(
        protected Discount $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(Discount::query())
            ->withCount(['codes', 'usages'])
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('shopify_discount_id', 'like', "%{$search}%")
                        ->orWhere('discount_type', 'like', "%{$search}%")
                        ->orWhere('method', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%")
                        ->orWhere('currency', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('shopify_updated_at')
            ->orderByDesc('id')
            ->paginate($rowsPerPage, ['*'], 'page', $page);
    }

    public function find(int $id)
    {
        return $this->applyTenantScope($this->model->newQuery())->findOrFail($id);
    }

    public function findForFrontend(int $id)
    {
        return $this->applyTenantScope(Discount::query())
            ->withCount(['codes', 'usages'])
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $discount = $this->find($id);
        $discount->fill($data);
        $discount->save();

        return $this->findForFrontend($discount->id);
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
