<?php

namespace App\Modules\Orders\Repositories\Eloquent;

use App\Modules\Orders\Models\DraftOrderItem;
use App\Modules\Orders\Repositories\Interfaces\DraftOrderItemsRepositoryInterface;

class FrontendDraftOrderItemsRepository implements DraftOrderItemsRepositoryInterface
{
    public function __construct(
        protected DraftOrderItem $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $draftOrderId = null)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(DraftOrderItem::query())
            ->with([
                'draftOrder:id,name,status,currency,total',
                'variant:id,title,sku',
            ])
            ->when($draftOrderId, fn ($query) => $query->where('draft_order_id', $draftOrderId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('product_title', 'like', "%{$search}%")
                        ->orWhere('variant_title', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
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
        return $this->applyTenantScope(DraftOrderItem::query())
            ->with([
                'draftOrder:id,name,status,currency,total',
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
