<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Controllers\Concerns\RespondsWithCmsPaginator;
use App\Modules\CMS\Repositories\Interfaces\MenusRepositoryInterface;
use App\Modules\CMS\Requests\MenusIndexRequest;
use App\Modules\CMS\Resources\MenuTableResource;

class MenuController extends Controller
{
    use RespondsWithCmsPaginator;

    public function __construct(protected MenusRepositoryInterface $repo) {}

    public function index(MenusIndexRequest $request)
    {
        $data = $request->validated();

        return $this->paginatedResponse(
            $this->repo->all($data['search'] ?? null, $data['rowsPerPage'] ?? 10, $data['page'] ?? 1, $this->requestFilters($data)),
            MenuTableResource::class,
        );
    }
}
