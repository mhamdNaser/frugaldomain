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

    public function findForFrontend(int $id)
    {
        return $this->applyTenantScope(
            $this->model->newQuery()->withCount('items')
        )->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $menu = $this->findForFrontend($id);
        $menu->fill($data);
        $menu->save();

        return $this->findForFrontend((int) $menu->id);
    }

    public function create(array $data)
    {
        $menu = $this->model->newQuery()->create($data);

        return $this->findForFrontend((int) $menu->id);
    }

    public function delete(int $id): void
    {
        $menu = $this->findForFrontend($id);
        $menu->delete();
    }
}
