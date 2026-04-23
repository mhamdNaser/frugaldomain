<?php

namespace App\Modules\Orders\Repositories\Eloquent;

use App\Modules\Orders\Models\DraftOrder;
use App\Modules\Orders\Repositories\Interfaces\DraftOrdersRepositoryInterface;

class FrontendDraftOrdersRepository implements DraftOrdersRepositoryInterface
{
    public function __construct(
        protected DraftOrder $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $customerId = null)
    {

        return $this->applyTenantScope(DraftOrder::query())
            ->with(['customer:id,display_name,email'])
            ->withCount('items')
            ->when($customerId, fn ($query) => $query->where('customer_id', $customerId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('currency', 'like', "%{$search}%")
                        ->orWhere('shopify_draft_order_id', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->paginate($rowsPerPage, ['*'], 'page', $page);
    }

    public function find(int $id)
    {
        return $this->applyTenantScope($this->model->newQuery())->findOrFail($id);
    }

    public function findForFrontend(int $id)
    {
        return $this->applyTenantScope(DraftOrder::query())
            ->with([
                'customer:id,display_name,email,phone',
                'items.variant.files',
                'items.variant.optionValues.option',
                'items.variant.product:id,title,handle,featured_image',
            ])
            ->withCount('items')
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $draftOrder = $this->find($id);
        $draftOrder->fill($data);
        $draftOrder->save();

        return $this->findForFrontend($draftOrder->id);
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
