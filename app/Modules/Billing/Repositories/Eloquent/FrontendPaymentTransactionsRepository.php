<?php

namespace App\Modules\Billing\Repositories\Eloquent;

use App\Modules\Billing\Models\PaymentTransaction;
use App\Modules\Billing\Repositories\Interfaces\PaymentTransactionsRepositoryInterface;

class FrontendPaymentTransactionsRepository implements PaymentTransactionsRepositoryInterface
{
    public function __construct(
        protected PaymentTransaction $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $orderId = null)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(PaymentTransaction::query())
            ->with(['order:id,order_number', 'refund:id,shopify_refund_id,total'])
            ->when($orderId, fn ($query) => $query->where('order_id', $orderId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('gateway', 'like', "%{$search}%")
                        ->orWhere('kind', 'like', "%{$search}%")
                        ->orWhere('transaction_reference', 'like', "%{$search}%")
                        ->orWhere('currency', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
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
        return $this->applyTenantScope(PaymentTransaction::query())
            ->with(['order:id,order_number,total,currency', 'refund:id,shopify_refund_id,total,currency'])
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
