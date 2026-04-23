<?php

namespace App\Modules\Catalog\Repositories\Eloquent\References;

use App\Modules\Catalog\Models\Collection;
use App\Modules\Catalog\Repositories\Interfaces\References\CollectionsRepositoryInterface;

class FrontendCollectionsRepository implements CollectionsRepositoryInterface
{
    public function __construct(
        protected Collection $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(Collection::query())
            ->withCount(['products', 'tags'])
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('handle', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('seo_title', 'like', "%{$search}%")
                        ->orWhere('seo_description', 'like', "%{$search}%");
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
        return $this->applyTenantScope(Collection::query())
            ->with([
                'metafields.metaobjects',
                'tags:id,name,slug',
                'products:id,title,handle,status',
            ])
            ->withCount(['products', 'tags'])
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $collection = $this->find($id);
        $collection->fill($data);
        $collection->save();

        return $this->findForFrontend($collection->id);
    }

    public function toggleStatus(int $id)
    {
        $collection = $this->find($id);
        $collection->is_active = !$collection->is_active;
        $collection->save();

        return $collection;
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
