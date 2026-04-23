<?php

namespace App\Modules\Fulfillment\Repositories\Eloquent;

use App\Modules\Fulfillment\Models\FulfillmentService;
use App\Modules\Fulfillment\Repositories\Interfaces\FulfillmentServicesRepositoryInterface;

class FrontendFulfillmentServicesRepository implements FulfillmentServicesRepositoryInterface
{
    public function __construct(
        protected FulfillmentService $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(FulfillmentService::query())
            ->withCount(['fulfillments', 'fulfillmentOrders'])
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('shopify_fulfillment_service_id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('service_name', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%");
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
        return $this->applyTenantScope(FulfillmentService::query())
            ->withCount(['fulfillments', 'fulfillmentOrders'])
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $service = $this->find($id);
        $service->fill($data);
        $service->save();

        return $this->findForFrontend($service->id);
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
