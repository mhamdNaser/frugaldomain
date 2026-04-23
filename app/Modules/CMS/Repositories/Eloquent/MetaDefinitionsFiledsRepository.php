<?php

namespace App\Modules\CMS\Repositories\Eloquent;

use App\Modules\CMS\Models\MetaobjectDefinitionField;
use App\Modules\CMS\Repositories\Eloquent\Concerns\PaginatesCmsTables;
use App\Modules\CMS\Repositories\Interfaces\MetaDefinitionsFiledsRepositoryInterface;

class MetaDefinitionsFiledsRepository implements MetaDefinitionsFiledsRepositoryInterface
{
    use PaginatesCmsTables;

    public function __construct(protected MetaobjectDefinitionField $model) {}

    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1, array $filters = [])
    {
        $filters = $this->normalizeFilters($filters);
        $query = $this->model->newQuery();

        // Force tenant isolation for partner users because this table does not have store_id.
        $user = auth()->user();
        if (
            $user
            && method_exists($user, 'hasRole')
            && $user->hasRole('partner')
            && !$user->hasRole('admin')
        ) {
            $storeId = $user->store?->id;
            abort_if(!$storeId, 404, 'No store is linked to the authenticated user.');

            $query->whereIn('metaobject_definition_id', function ($sub) use ($storeId) {
                $sub->from('metaobject_definitions')
                    ->select('id')
                    ->where('store_id', $storeId);
            });
        }

        return $this->paginateQuery(
            $query,
            $search,
            $rowsPerPage,
            $page,
            $filters,
            ['field_key', 'name', 'type'],
            ['metaobject_definition_id', 'required'],
            'created_at',
        );
    }

    private function normalizeFilters(array $filters): array
    {
        if (
            array_key_exists('meta_definition_id', $filters)
            && !array_key_exists('metaobject_definition_id', $filters)
        ) {
            $filters['metaobject_definition_id'] = $filters['meta_definition_id'];
        }

        return $filters;
    }
}
