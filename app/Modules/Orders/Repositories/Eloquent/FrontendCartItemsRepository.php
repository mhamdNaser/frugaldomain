<?php

namespace App\Modules\Orders\Repositories\Eloquent;

use App\Modules\Orders\Models\CartItem;
use App\Modules\Orders\Repositories\Interfaces\CartItemsRepositoryInterface;

class FrontendCartItemsRepository implements CartItemsRepositoryInterface
{
    public function __construct(
        protected CartItem $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $cartId = null)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(CartItem::query())
            ->with([
                'cart:id,status,currency,total_amount',
                'variant:id,title,sku',
            ])
            ->when($cartId, fn ($query) => $query->where('cart_id', $cartId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('quantity', 'like', "%{$search}%")
                        ->orWhere('unit_price', 'like', "%{$search}%")
                        ->orWhere('total_price', 'like', "%{$search}%");
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
        return $this->applyTenantScope(CartItem::query())
            ->with([
                'cart:id,status,currency,total_amount',
                'variant:id,title,sku',
            ])
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $item = $this->find($id);
        $item->fill($data);
        $item->save();

        return $this->findForFrontend($item->id);
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
