<?php

namespace App\Modules\Catalog\Repositories\Eloquent\References;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Repositories\Interfaces\References\CategoriesRepositoryInterface;

class FrontendCategoriesRepository implements CategoriesRepositoryInterface
{
    public function __construct(
        protected Category $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(Category::query())
            ->withCount('products')
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('shopify_category_id', 'like', "%{$search}%");
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
        return $this->applyTenantScope(Category::query())
            ->with('products:id,category_id,title,handle,status')
            ->withCount('products')
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $category = $this->find($id);
        $category->fill($data);
        $category->save();

        return $this->findForFrontend($category->id);
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
