<?php

namespace App\Modules\Orders\Repositories\Eloquent;

use App\Modules\Orders\Models\Cart;
use App\Modules\Orders\Repositories\Interfaces\CartsRepositoryInterface;

class FrontendCartsRepository implements CartsRepositoryInterface
{
    public function __construct(
        protected Cart $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $customerId = null)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(Cart::query())
            ->with(['customer:id,display_name,email'])
            ->withCount('items')
            ->when($customerId, fn ($query) => $query->where('customer_id', $customerId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('status', 'like', "%{$search}%")
                        ->orWhere('currency', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($rowsPerPage, ['*'], 'page', $page);
    }

    public function find(int $id)
    {
        return $this->applyTenantScope($this->model->newQuery())->findOrFail($id);
    }

    public function findForFrontend(int $id)
    {
        return $this->applyTenantScope(Cart::query())
            ->with([
                'customer:id,display_name,email,phone',
                'items',
            ])
            ->withCount('items')
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $cart = $this->find($id);
        $cart->fill($data);
        $cart->save();

        return $this->findForFrontend($cart->id);
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
