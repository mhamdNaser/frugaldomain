<?php

namespace App\Modules\Catalog\Repositories\Eloquent\References;

use App\Modules\Catalog\Models\Option;
use App\Modules\Catalog\Repositories\Interfaces\References\OptionsRepositoryInterface;

class FrontendOptionsRepository implements OptionsRepositoryInterface
{
    public function __construct(
        protected Option $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(Option::query())
            ->with(['values:id,option_id,label,value'])
            ->withCount('products')
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhereHas('values', function ($query) use ($search) {
                            $query->where('label', 'like', "%{$search}%")
                                ->orWhere('value', 'like', "%{$search}%");
                        });
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
        return $this->applyTenantScope(Option::query())
            ->with([
                'values:id,option_id,label,value',
                'products:id,title,handle,status',
            ])
            ->withCount('products')
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $option = $this->find($id);
        $option->fill($data);
        $option->save();

        return $this->findForFrontend($option->id);
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
