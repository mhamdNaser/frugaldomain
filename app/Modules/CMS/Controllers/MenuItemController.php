<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Controllers\Concerns\RespondsWithCmsPaginator;
use App\Modules\CMS\Repositories\Interfaces\MenuItemsRepositoryInterface;
use App\Modules\CMS\Requests\MenuItemsIndexRequest;
use App\Modules\CMS\Resources\MenuItemTableResource;

class MenuItemController extends Controller
{
    use RespondsWithCmsPaginator;

    public function __construct(protected MenuItemsRepositoryInterface $repo) {}

    public function index(MenuItemsIndexRequest $request)
    {
        $data = $request->validated();

        return $this->paginatedResponse(
            $this->repo->all($data['search'] ?? null, $data['rowsPerPage'] ?? 10, $data['page'] ?? 1, $this->requestFilters($data)),
            MenuItemTableResource::class,
        );
    }
}

