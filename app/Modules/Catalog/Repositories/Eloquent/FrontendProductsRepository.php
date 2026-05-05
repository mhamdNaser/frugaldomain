<?php

namespace App\Modules\Catalog\Repositories\Eloquent;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Repositories\Interfaces\ProductsRepositoryInterface;
use Illuminate\Support\Arr;

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
                'tags',
                'files',
                'productMedia',
                'metafields.metaobjects',
                'options.values',
                'variants' => fn ($query) => $query->orderBy('position')->orderBy('id'),
                'variants.variantImage',
                'variants.variantImages',
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
        $relations = Arr::only($data, ['collection_ids', 'option_ids', 'tag_ids']);
        $attributes = Arr::except($data, ['collection_ids', 'option_ids', 'tag_ids']);

        $product->fill($attributes);
        $product->save();
        $this->syncRelations($product, $relations);

        return $this->findForFrontend($product->id);
    }

    public function create(array $data)
    {
        $relations = Arr::only($data, ['collection_ids', 'option_ids', 'tag_ids']);
        $attributes = Arr::except($data, ['collection_ids', 'option_ids', 'tag_ids']);

        $product = $this->model->newQuery()->create($attributes);
        $this->syncRelations($product, $relations);

        return $this->findForFrontend((int) $product->id);
    }

    public function delete(int $id): void
    {
        $product = $this->find($id);
        $product->delete();
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

    private function syncRelations(Product $product, array $relations): void
    {
        if (array_key_exists('collection_ids', $relations)) {
            $collectionIds = collect((array) $relations['collection_ids'])
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $pivotPayload = $collectionIds
                ->mapWithKeys(fn ($id, $index) => [
                    $id => [
                        'store_id' => $product->store_id,
                        'position' => $index,
                        'added_via' => 'manual',
                    ],
                ])
                ->toArray();

            $product->collections()->sync($pivotPayload);
        }

        if (array_key_exists('option_ids', $relations)) {
            $optionIds = collect((array) $relations['option_ids'])
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $pivotPayload = $optionIds
                ->mapWithKeys(fn ($id, $index) => [
                    $id => [
                        'store_id' => $product->store_id,
                        'position' => $index,
                    ],
                ])
                ->toArray();

            $product->options()->sync($pivotPayload);
        }

        if (array_key_exists('tag_ids', $relations)) {
            $tagIds = collect((array) $relations['tag_ids'])
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->toArray();

            $product->tags()->sync($tagIds);
        }
    }
}
