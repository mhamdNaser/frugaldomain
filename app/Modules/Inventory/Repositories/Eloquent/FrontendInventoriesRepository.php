<?php

namespace App\Modules\Inventory\Repositories\Eloquent;

use App\Modules\Inventory\Models\InventoryLevel;
use App\Modules\Inventory\Repositories\Interfaces\InventoriesRepositoryInterface;

class FrontendInventoriesRepository implements InventoriesRepositoryInterface
{
    public function __construct(
        protected InventoryLevel $model
    ) {}

    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1)
    {
        $rowsPerPage = max(1, min($rowsPerPage, 100));
        $page = max(1, $page);

        return $this->applyTenantScope(InventoryLevel::query())
            ->with([
                'variant:id,product_id,title,sku,shopify_variant_id',
                'variant.files',
                'variant.variantImage',
                'variant.product:id,title,handle,featured_image',
                'location:id,store_id,shopify_location_id,name,city,country,is_active',
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('shopify_location_id', 'like', "%{$search}%")
                        ->orWhere('inventory_item_id', 'like', "%{$search}%")
                        ->orWhere('available', 'like', "%{$search}%")
                        ->orWhereHas('variant', fn ($q) => $q
                            ->where('sku', 'like', "%{$search}%")
                            ->orWhere('title', 'like', "%{$search}%")
                            ->orWhere('shopify_variant_id', 'like', "%{$search}%")
                            ->orWhereHas('product', fn ($productQuery) => $productQuery
                                ->where('title', 'like', "%{$search}%")
                                ->orWhere('handle', 'like', "%{$search}%")));
                });
            })
            ->orderByDesc('shopify_updated_at')
            ->orderByDesc('id')
            ->paginate($rowsPerPage, ['*'], 'page', $page);
    }

    public function find(int $id)
    {
        return $this->applyTenantScope($this->model->newQuery())->findOrFail($id);
    }

    public function findForFrontend(int $id)
    {
        return $this->applyTenantScope(InventoryLevel::query())
            ->with([
                'variant:id,product_id,title,sku,shopify_variant_id',
                'variant.files',
                'variant.variantImage',
                'variant.product:id,title,handle,shopify_product_id,featured_image',
                'location:id,store_id,shopify_location_id,name,address,city,country,is_active',
            ])
            ->findOrFail($id);
    }

    public function create(array $data)
    {
        $inventory = $this->model->newQuery()->create($data);

        return $this->findForFrontend((int) $inventory->id);
    }

    public function update(int $id, array $data)
    {
        $inventory = $this->find($id);
        $inventory->fill($data);
        $inventory->save();

        return $this->findForFrontend((int) $inventory->id);
    }

    public function delete(int $id): void
    {
        $inventory = $this->find($id);
        $inventory->delete();
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
