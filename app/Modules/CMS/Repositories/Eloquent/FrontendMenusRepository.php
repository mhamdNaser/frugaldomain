<?php

namespace App\Modules\CMS\Repositories\Eloquent;

use App\Modules\CMS\Models\Menu;
use App\Modules\CMS\Repositories\Eloquent\Concerns\PaginatesCmsTables;
use App\Modules\CMS\Repositories\Interfaces\MenusRepositoryInterface;

class FrontendMenusRepository implements MenusRepositoryInterface
{
    use PaginatesCmsTables;

    public function __construct(protected Menu $model) {}

    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1, array $filters = [])
    {
        return $this->paginateQuery(
            $this->model->newQuery()->withCount('items'),
            $search,
            $rowsPerPage,
            $page,
            $filters,
            ['title', 'handle', 'shopify_menu_id'],
            [],
            'created_at',
        );
    }
}
