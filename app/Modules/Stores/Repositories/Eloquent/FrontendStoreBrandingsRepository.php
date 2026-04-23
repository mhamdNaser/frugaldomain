<?php

namespace App\Modules\Stores\Repositories\Eloquent;

use App\Modules\Stores\Models\StoreBranding;
use App\Modules\Stores\Repositories\Interfaces\StoreBrandingsRepositoryInterface;

class FrontendStoreBrandingsRepository implements StoreBrandingsRepositoryInterface
{
    public function __construct(
        protected StoreBranding $model
    ) {}

    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1, ?string $storeId = null)
    {
        $rowsPerPage = max(1, min($rowsPerPage, 100));
        $page = max(1, $page);

        $query = $this->applyTenantScope($this->model->newQuery())
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('store_id', 'like', "%{$search}%")
                        ->orWhere('font_family', 'like', "%{$search}%")
                        ->orWhere('primary_color', 'like', "%{$search}%")
                        ->orWhere('secondary_color', 'like', "%{$search}%")
                        ->orWhere('logo_url', 'like', "%{$search}%")
                        ->orWhere('favicon_url', 'like', "%{$search}%");
                });
            });

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($rowsPerPage, ['*'], 'page', $page);
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

