<?php

namespace App\Modules\Billing\Repositories\Eloquent;

use App\Modules\Billing\Models\Refund;
use App\Modules\Billing\Repositories\Interfaces\RefundsRepositoryInterface;

class FrontendRefundsRepository implements RefundsRepositoryInterface
{
    public function __construct(
        protected Refund $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $orderId = null)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(Refund::query())
            ->with(['order:id,order_number,status'])
            ->withCount(['items', 'transactions'])
            ->when($orderId, fn ($query) => $query->where('order_id', $orderId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('shopify_refund_id', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%")
                        ->orWhere('currency', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('processed_at')
            ->orderByDesc('id')
            ->paginate($rowsPerPage, ['*'], 'page', $page);
    }

    public function find(int $id)
    {
        return $this->applyTenantScope($this->model->newQuery())->findOrFail($id);
    }

    public function findForFrontend(int $id)
    {
        return $this->applyTenantScope(Refund::query())
            ->with(['order:id,order_number,total,currency', 'items', 'transactions'])
            ->withCount(['items', 'transactions'])
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $refund = $this->find($id);
        $refund->fill($data);
        $refund->save();

        return $this->findForFrontend($refund->id);
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
