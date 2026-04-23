<?php

namespace App\Modules\Marketing\Repositories\Eloquent;

use App\Modules\Catalog\Models\SellingPlan;
use App\Modules\Marketing\Repositories\Interfaces\SellingPlansRepositoryInterface;

class FrontendSellingPlansRepository implements SellingPlansRepositoryInterface
{
    public function __construct(
        protected SellingPlan $model
    ) {}

    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1, ?int $sellingPlanGroupId = null)
    {
        $rowsPerPage = max(1, min($rowsPerPage, 100));
        $page = max(1, $page);

        return $this->applyTenantScope($this->model->newQuery())
            ->when($sellingPlanGroupId, fn ($query) => $query->where('selling_plan_group_id', $sellingPlanGroupId))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('shopify_selling_plan_id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($rowsPerPage, ['*'], 'page', $page);
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

