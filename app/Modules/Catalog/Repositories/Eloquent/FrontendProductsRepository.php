<?php

namespace App\Modules\Catalog\Repositories\Eloquent;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Repositories\Interfaces\ProductsRepositoryInterface;

class FrontendProductsRepository implements ProductsRepositoryInterface
{
    public function __construct(
        protected Product $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1)
    {
        return $this->applyTenantScope(Product::query())
            ->with([
                'vendor:id,name,slug',
                'productType:id,name,slug',
                'category:id,name,slug',
                'collections:id,title,handle',
                'files',
            ])
            ->withCount(['variants', 'collections', 'files'])
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('handle', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('vendor', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('productType', fn ($query) => $query->where('name', 'like', "%{$search}%"));
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
        return $this->applyTenantScope(Product::query())
            ->with([
                'vendor',
                'productType',
                'category',
                'collections',
                'files',
                'productMedia',
                'metafields.metaobjects',
                'options.values',
                'variants' => fn ($query) => $query->orderBy('position')->orderBy('id'),
                'variants.files',
                'variants.optionValues.option',
                'variants.metafields.metaobjects',
                'variants.priceLists',
                'variants.priceListItems.priceList',
                'variants.inventories.location',
                'variants.inventoryLevels.location',
                'variants.inventoryMovements.location',
            ])
            ->withCount(['variants', 'collections', 'files'])
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $product = $this->find($id);
        $product->fill($data);
        $product->save();

        return $this->findForFrontend($product->id);
    }

    public function toggleStatus(int $id)
    {
        $product = $this->find($id);
        $product->status = $product->status === 'active' ? 'draft' : 'active';
        $product->save();

        return $product;
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
