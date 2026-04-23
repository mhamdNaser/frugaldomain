<?php

namespace App\Modules\Marketing\Repositories\Eloquent;

use App\Modules\Catalog\Models\SellingPlanSubscription;
use App\Modules\Marketing\Repositories\Interfaces\SellingPlanSubscriptionsRepositoryInterface;

class FrontendSellingPlanSubscriptionsRepository implements SellingPlanSubscriptionsRepositoryInterface
{
    public function __construct(
        protected SellingPlanSubscription $model
    ) {}

    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1, ?int $customerId = null)
    {
        $rowsPerPage = max(1, min($rowsPerPage, 100));
        $page = max(1, $page);

        return $this->applyTenantScope($this->model->newQuery())
            ->when($customerId, fn ($query) => $query->where('customer_id', $customerId))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('shopify_subscription_contract_id', 'like', "%{$search}%")
                        ->orWhere('shopify_customer_id', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('currency', 'like', "%{$search}%");
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

