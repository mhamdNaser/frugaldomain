<?php

namespace App\Modules\Shipping\Repositories\Eloquent;

use App\Modules\Shipping\Models\ShippingZone;
use App\Modules\Shipping\Repositories\Interfaces\ShippingZonesRepositoryInterface;

class FrontendShippingZonesRepository implements ShippingZonesRepositoryInterface
{
    public function __construct(
        protected ShippingZone $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(ShippingZone::query())
            ->withCount(['methods', 'rates'])
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('shopify_zone_id', 'like', "%{$search}%")
                        ->orWhere('shopify_profile_id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
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
        return $this->applyTenantScope(ShippingZone::query())
            ->withCount(['methods', 'rates'])
            ->with([
                'methods' => function ($query) {
                    $query->with(['rates' => fn ($ratesQuery) => $ratesQuery->orderByDesc('id')])
                        ->orderByDesc('id');
                },
                'rates' => fn ($query) => $query->orderByDesc('id'),
            ])
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $zone = $this->find($id);
        $zone->fill($data);
        $zone->save();

        return $this->findForFrontend($zone->id);
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
