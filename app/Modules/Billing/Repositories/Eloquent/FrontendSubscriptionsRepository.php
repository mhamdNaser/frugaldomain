<?php

namespace App\Modules\Billing\Repositories\Eloquent;

use App\Modules\Billing\Models\Subscription;
use App\Modules\Billing\Repositories\Interfaces\SubscriptionsRepositoryInterface;

class FrontendSubscriptionsRepository implements SubscriptionsRepositoryInterface
{
    public function __construct(
        protected Subscription $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $storeId = null, $planId = null)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);
        $storeId = $this->resolveTenantStoreId($storeId);

        return $this->applyTenantScope(Subscription::query())
            ->with('plan:id,name,price,billing_interval,is_active')
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->when($planId, fn ($query) => $query->where('plan_id', $planId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('status', 'like', "%{$search}%")
                        ->orWhereHas('plan', fn ($query) => $query->where('name', 'like', "%{$search}%"));
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
        return $this->applyTenantScope(Subscription::query())
            ->with('plan')
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $subscription = $this->find($id);
        $subscription->fill($data);
        $subscription->save();

        return $this->findForFrontend($subscription->id);
    }

    private function resolveTenantStoreId($requestedStoreId = null)
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

            return $storeId;
        }

        return $requestedStoreId;
    }

    private function applyTenantScope($query)
    {
        $storeId = $this->resolveTenantStoreId();
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        return $query;
    }
}
