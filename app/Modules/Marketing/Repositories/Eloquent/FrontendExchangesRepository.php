<?php

namespace App\Modules\Marketing\Repositories\Eloquent;

use App\Modules\Fulfillment\Models\Exchange;
use App\Modules\Marketing\Repositories\Interfaces\ExchangesRepositoryInterface;

class FrontendExchangesRepository implements ExchangesRepositoryInterface
{
    public function __construct(
        protected Exchange $model
    ) {}

    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1, ?int $orderReturnId = null)
    {
        $rowsPerPage = max(1, min($rowsPerPage, 100));
        $page = max(1, $page);

        return $this->applyTenantScope($this->model->newQuery())
            ->when($orderReturnId, fn ($query) => $query->where('order_return_id', $orderReturnId))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('shopify_exchange_line_item_id', 'like', "%{$search}%")
                        ->orWhere('shopify_line_item_id', 'like', "%{$search}%");
                });
            })
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

