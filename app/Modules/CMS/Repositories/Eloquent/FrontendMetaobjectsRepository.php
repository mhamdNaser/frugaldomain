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

    public function findForFrontend(int $id)
    {
        return $this->applyTenantScope(
            $this->model->newQuery()->withCount('metafields')
        )->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $metaobject = $this->findForFrontend($id);
        $metaobject->fill($data);
        $metaobject->save();

        return $this->findForFrontend((int) $metaobject->id);
    }

    public function create(array $data)
    {
        $metaobject = $this->model->newQuery()->create($data);

        return $this->findForFrontend((int) $metaobject->id);
    }

    public function delete(int $id): void
    {
        $metaobject = $this->findForFrontend($id);
        $metaobject->delete();
    }
}
