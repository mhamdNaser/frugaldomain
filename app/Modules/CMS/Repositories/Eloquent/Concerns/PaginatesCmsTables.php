<?php

namespace App\Modules\CMS\Repositories\Eloquent\Concerns;

use Illuminate\Support\Facades\Schema;

trait PaginatesCmsTables
{
    protected function paginateQuery($query, ?string $search, int $rowsPerPage, int $page, array $filters, array $searchColumns, array $allowedFilters = [], string $orderColumn = 'created_at')
    {
        $rowsPerPage = max(1, min($rowsPerPage, 100));
        $page = max(1, $page);
        $query = $this->applyTenantScope($query);

        return $query
            ->when($filters, function ($query) use ($filters, $allowedFilters) {
                foreach ($allowedFilters as $field) {
                    if (($filters[$field] ?? null) !== null && $filters[$field] !== '') {
                        $query->where($field, $filters[$field]);
                    }
                }
            })
            ->when($search && $searchColumns, function ($query) use ($search, $searchColumns) {
                $query->where(function ($query) use ($search, $searchColumns) {
                    foreach ($searchColumns as $column) {
                        $query->orWhere($column, 'like', "%{$search}%");
                    }
                });
            })
            ->orderByDesc($orderColumn)
            ->when($this->hasKeyColumn($query), fn ($query) => $query->orderByDesc($query->getModel()->getKeyName()))
            ->paginate($rowsPerPage, ['*'], 'page', $page);
    }

    private function hasKeyColumn($query): bool
    {
        $model = $query->getModel();

        return Schema::hasColumn($model->getTable(), $model->getKeyName());
    }

    private function applyTenantScope($query)
    {
        $user = auth()->user();

        if (
            !$user
            || !method_exists($user, 'hasRole')
            || !$user->hasRole('partner')
            || $user->hasRole('admin')
        ) {
            return $query;
        }

        $model = $query->getModel();
        $table = $model->getTable();

        if (!Schema::hasColumn($table, 'store_id')) {
            return $query;
        }

        $storeId = $user->store?->id;
        abort_if(!$storeId, 404, 'No store is linked to the authenticated user.');

        return $query->where("{$table}.store_id", $storeId);
    }
}
