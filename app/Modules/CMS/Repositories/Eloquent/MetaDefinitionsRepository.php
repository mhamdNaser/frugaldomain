<?php

namespace App\Modules\CMS\Repositories\Eloquent;

use App\Modules\CMS\Models\MetaobjectDefinition;
use App\Modules\CMS\Repositories\Eloquent\Concerns\PaginatesCmsTables;
use App\Modules\CMS\Repositories\Interfaces\MetaDefinitionsRepositoryInterface;
use Illuminate\Support\Facades\DB;

class MetaDefinitionsRepository implements MetaDefinitionsRepositoryInterface
{
    use PaginatesCmsTables;

    public function __construct(protected MetaobjectDefinition $model) {}

    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1, array $filters = [])
    {
        $query = $this->model->newQuery()
            ->select('metaobject_definitions.*')
            ->selectSub(
                DB::table('metaobject_definition_fields')
                    ->selectRaw('count(*)')
                    ->whereColumn('metaobject_definition_fields.metaobject_definition_id', 'metaobject_definitions.id'),
                'fields_count'
            );

        return $this->paginateQuery(
            $query,
            $search,
            $rowsPerPage,
            $page,
            $filters,
            ['shopify_metaobject_definition_id', 'type', 'name', 'display_name_key'],
            [],
            'created_at',
        );
    }
}
