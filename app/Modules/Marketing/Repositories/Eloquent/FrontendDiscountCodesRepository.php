<?php

namespace App\Modules\Marketing\Repositories\Eloquent;

use App\Modules\Marketing\Models\DiscountCode;
use App\Modules\Marketing\Repositories\Interfaces\DiscountCodesRepositoryInterface;

class FrontendDiscountCodesRepository implements DiscountCodesRepositoryInterface
{
    public function __construct(
        protected DiscountCode $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1, $discountId = null)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(DiscountCode::query())
            ->with(['discount:id,title,discount_type,status'])
            ->when($discountId, fn ($query) => $query->where('discount_id', $discountId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('shopify_discount_code_id', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhereHas('discount', function ($discountQuery) use ($search) {
                            $discountQuery->where('title', 'like', "%{$search}%");
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
        return $this->applyTenantScope(DiscountCode::query())
            ->with(['discount:id,title,discount_type,status'])
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $code = $this->find($id);
        $code->fill($data);
        $code->save();

        return $this->findForFrontend($code->id);
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
