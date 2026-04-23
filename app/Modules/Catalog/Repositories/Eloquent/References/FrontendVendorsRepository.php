<?php

namespace App\Modules\Catalog\Repositories\Eloquent\References;

use App\Modules\Catalog\Models\Vendor;
use App\Modules\Catalog\Repositories\Interfaces\References\VendorsRepositoryInterface;

class FrontendVendorsRepository implements VendorsRepositoryInterface
{
    public function __construct(
        protected Vendor $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(Vendor::query())
            ->withCount(['products', 'tags'])
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('meta_title', 'like', "%{$search}%")
                        ->orWhere('meta_description', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('contact_phone', 'like', "%{$search}%");
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
        return $this->applyTenantScope(Vendor::query())
            ->with([
                'metafields.metaobjects',
                'tags:id,name,slug',
            ])
            ->withCount(['products', 'tags'])
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $vendor = $this->find($id);
        $vendor->fill($data);
        $vendor->save();

        return $this->findForFrontend($vendor->id);
    }

    public function toggleStatus(int $id)
    {
        $vendor = $this->find($id);
        $vendor->is_active = !$vendor->is_active;
        $vendor->save();

        return $vendor;
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
