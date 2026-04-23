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
}
