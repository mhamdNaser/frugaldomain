<?php

namespace App\Modules\CMS\Repositories\Eloquent;

use App\Modules\CMS\Models\Blog;
use App\Modules\CMS\Repositories\Eloquent\Concerns\PaginatesCmsTables;
use App\Modules\CMS\Repositories\Interfaces\BlogsRepositoryInterface;

class FrontendBlogsRepository implements BlogsRepositoryInterface
{
    use PaginatesCmsTables;

    public function __construct(protected Blog $model) {}

    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1, array $filters = [])
    {
        return $this->paginateQuery(
            $this->model->newQuery()->withCount('articles'),
            $search,
            $rowsPerPage,
            $page,
            $filters,
            ['title', 'handle', 'comment_policy', 'seo_title', 'seo_description'],
            [],
            'published_at',
        );
    }
}
