<?php

namespace App\Modules\CMS\Repositories\Eloquent;

use App\Modules\CMS\Models\Page;
use App\Modules\CMS\Repositories\Eloquent\Concerns\PaginatesCmsTables;
use App\Modules\CMS\Repositories\Interfaces\PagesRepositoryInterface;

class FrontendPagesRepository implements PagesRepositoryInterface
{
    use PaginatesCmsTables;

    public function __construct(protected Page $model) {}

    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1, array $filters = [])
    {
        return $this->paginateQuery(
            $this->model->newQuery(),
            $search,
            $rowsPerPage,
            $page,
            $filters,
            ['title', 'handle', 'author', 'seo_title', 'seo_description'],
            [],
            'published_at',
        );
    }

    public function findForFrontend(int $id)
    {
        return $this->applyTenantScope($this->model->newQuery())->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $page = $this->findForFrontend($id);
        $page->fill($data);
        $page->save();

        return $this->findForFrontend((int) $page->id);
    }

    public function create(array $data)
    {
        $page = $this->model->newQuery()->create($data);

        return $this->findForFrontend((int) $page->id);
    }

    public function delete(int $id): void
    {
        $page = $this->findForFrontend($id);
        $page->delete();
    }
}
