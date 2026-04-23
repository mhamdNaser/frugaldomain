<?php

namespace App\Modules\Fulfillment\Repositories\Eloquent;

use App\Modules\Fulfillment\Models\FulfillmentTracking;
use App\Modules\Fulfillment\Repositories\Interfaces\FulfillmentTrackingRepositoryInterface;

class FrontendFulfillmentTrackingRepository implements FulfillmentTrackingRepositoryInterface
{
    public function __construct(
        protected FulfillmentTracking $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $fulfillmentId = null)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(FulfillmentTracking::query())
            ->with(['fulfillment:id,order_id,status,tracking_company,tracking_number'])
            ->when($fulfillmentId, fn ($query) => $query->where('fulfillment_id', $fulfillmentId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('company', 'like', "%{$search}%")
                        ->orWhere('number', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%");
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
        return $this->applyTenantScope(FulfillmentTracking::query())
            ->with(['fulfillment:id,order_id,status,tracking_company,tracking_number'])
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $tracking = $this->find($id);
        $tracking->fill($data);
        $tracking->save();

        return $this->findForFrontend($tracking->id);
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
