<?php

namespace App\Modules\CMS\Repositories\Eloquent;

use App\Modules\CMS\Models\MenuItem;
use App\Modules\CMS\Repositories\Eloquent\Concerns\PaginatesCmsTables;
use App\Modules\CMS\Repositories\Interfaces\MenuItemsRepositoryInterface;

class FrontendMenuItemsRepository implements MenuItemsRepositoryInterface
{
    use PaginatesCmsTables;

    public function __construct(protected MenuItem $model) {}

    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1, array $filters = [])
    {
        return $this->paginateQuery(
            $this->model->newQuery(),
            $search,
            $rowsPerPage,
            $page,
            $filters,
            ['title', 'type', 'url', 'shopify_menu_item_id'],
            ['menu_id', 'parent_id'],
            'created_at',
        );
    }
}

