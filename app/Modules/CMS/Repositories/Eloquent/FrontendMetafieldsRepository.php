<?php

namespace App\Modules\CMS\Repositories\Eloquent;

use App\Modules\CMS\Models\Metafield;
use App\Modules\CMS\Repositories\Eloquent\Concerns\PaginatesCmsTables;
use App\Modules\CMS\Repositories\Interfaces\MetafieldsRepositoryInterface;

class FrontendMetafieldsRepository implements MetafieldsRepositoryInterface
{
    use PaginatesCmsTables;

    public function __construct(protected Metafield $model) {}

    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1, array $filters = [])
    {
        $query = $this->model->newQuery()->withCount('metaobjects');

        if (($filters['metaobject_id'] ?? null) !== null && $filters['metaobject_id'] !== '') {
            $query->whereHas('metaobjects', fn ($query) => $query->where('metaobjects.id', $filters['metaobject_id']));
        }

        return $this->paginateQuery(
            $query,
            $search,
            $rowsPerPage,
            $page,
            $filters,
            ['metafieldable_type', 'namespace', 'key', 'type'],
            ['metafieldable_id'],
            'created_at',
        );
    }
}
