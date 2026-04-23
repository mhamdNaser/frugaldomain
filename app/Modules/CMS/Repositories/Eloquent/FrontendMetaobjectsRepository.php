<?php

namespace App\Modules\CMS\Repositories\Eloquent;

use App\Modules\CMS\Models\MetaObject;
use App\Modules\CMS\Repositories\Eloquent\Concerns\PaginatesCmsTables;
use App\Modules\CMS\Repositories\Interfaces\MetaobjectsRepositoryInterface;

class FrontendMetaobjectsRepository implements MetaobjectsRepositoryInterface
{
    use PaginatesCmsTables;

    public function __construct(protected MetaObject $model) {}

    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1, array $filters = [])
    {
        return $this->paginateQuery(
            $this->model->newQuery()->withCount('metafields'),
            $search,
            $rowsPerPage,
            $page,
            $filters,
            ['shopify_metaobject_id', 'type'],
            [],
            'created_at',
        );
    }
}
