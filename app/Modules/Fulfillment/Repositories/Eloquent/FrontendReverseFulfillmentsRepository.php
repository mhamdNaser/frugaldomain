<?php

namespace App\Modules\Fulfillment\Repositories\Eloquent;

use App\Modules\Fulfillment\Models\ReverseFulfillment;
use App\Modules\Fulfillment\Repositories\Interfaces\ReverseFulfillmentsRepositoryInterface;

class FrontendReverseFulfillmentsRepository implements ReverseFulfillmentsRepositoryInterface
{
    public function __construct(
        protected ReverseFulfillment $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $orderReturnId = null)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(ReverseFulfillment::query())
            ->with(['orderReturn:id,order_id,shopify_return_id,status,name'])
            ->when($orderReturnId, fn ($query) => $query->where('order_return_id', $orderReturnId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('shopify_reverse_fulfillment_order_id', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
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
        return $this->applyTenantScope(ReverseFulfillment::query())
            ->with(['orderReturn:id,order_id,shopify_return_id,status,name'])
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $reverse = $this->find($id);
        $reverse->fill($data);
        $reverse->save();

        return $this->findForFrontend($reverse->id);
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
