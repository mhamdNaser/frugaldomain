<?php

namespace App\Modules\Catalog\Repositories\Eloquent\References;

use App\Modules\Catalog\Models\Tag;
use App\Modules\Catalog\Repositories\Interfaces\References\TagsRepositoryInterface;

class FrontendTagsRepository implements TagsRepositoryInterface
{
    public function __construct(
        protected Tag $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(Tag::query())
            ->withCount(['products', 'collections', 'vendors'])
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
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
        return $this->applyTenantScope(Tag::query())
            ->with([
                'products:id,title,handle,status',
                'collections:id,title,handle',
                'vendors:id,name,slug',
            ])
            ->withCount(['products', 'collections', 'vendors'])
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $tag = $this->find($id);
        $tag->fill($data);
        $tag->save();

        return $this->findForFrontend($tag->id);
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
