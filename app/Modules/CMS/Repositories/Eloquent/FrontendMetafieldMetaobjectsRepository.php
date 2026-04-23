<?php

namespace App\Modules\CMS\Repositories\Eloquent;

use App\Modules\CMS\Models\MetaFieldMetaObject;
use App\Modules\CMS\Repositories\Eloquent\Concerns\PaginatesCmsTables;
use App\Modules\CMS\Repositories\Interfaces\MetafieldMetaobjectsRepositoryInterface;

class FrontendMetafieldMetaobjectsRepository implements MetafieldMetaobjectsRepositoryInterface
{
    use PaginatesCmsTables;

    public function __construct(protected MetaFieldMetaObject $model) {}

    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1, array $filters = [])
    {
        return $this->paginateQuery(
            $this->model->newQuery()->with(['metafield:id,namespace,key,type', 'metaobject:id,type,shopify_metaobject_id']),
            $search,
            $rowsPerPage,
            $page,
            $filters,
            [],
            ['metafield_id', 'metaobject_id'],
            'metafield_id',
        );
    }
}
