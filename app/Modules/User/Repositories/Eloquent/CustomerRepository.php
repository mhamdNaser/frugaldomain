<?php

namespace App\Modules\User\Repositories\Eloquent;

use App\Modules\User\Models\Customer;
use App\Modules\User\Repositories\Interfaces\CustomerRepositoryInterface;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function __construct(
        protected Customer $model
    ) {}

    public function getAllByStore(string $storeId, ?string $search = null, int $rowsPerPage = 10)
    {
        return $this->model::query()
            ->where('store_id', $storeId)
            ->withCount('orders')
            ->withSum([
                'orders as orders_total_spent' => fn ($q) => $q->where('payment_status', 'paid'),
            ], 'total')
            ->when($search, function ($q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('display_name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhere('shopify_customer_id', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($rowsPerPage);
    }

    public function findForStoreWithDetails(string $storeId, int $id)
    {
        return $this->model::query()
            ->with([
                'addresses',
                'marketingConsent',
                'orders.items',
                'draftOrders.items',
                'carts.items',
                'devices',
                'appSessions.device',
                'sellingPlanSubscriptions',
            ])
            ->withCount('orders')
            ->withSum([
                'orders as orders_total_spent' => fn ($q) => $q->where('payment_status', 'paid'),
            ], 'total')
            ->where('store_id', $storeId)
            ->find($id);
    }
}

