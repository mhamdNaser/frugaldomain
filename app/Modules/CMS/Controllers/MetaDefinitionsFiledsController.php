<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Controllers\Concerns\RespondsWithCmsPaginator;
use App\Modules\CMS\Repositories\Interfaces\MetaDefinitionsFiledsRepositoryInterface;
use App\Modules\CMS\Requests\MetaDefinitionsFiledsRequest;
use App\Modules\CMS\Resources\MetaDefinitionsFiledsResource;

class MetaDefinitionsFiledsController extends Controller
{
    use RespondsWithCmsPaginator;

    public function __construct(protected MetaDefinitionsFiledsRepositoryInterface $repo) {}

    public function index(MetaDefinitionsFiledsRequest $request)
    {
        $data = $request->validated();

        return $this->paginatedResponse(
            $this->repo->all($data['search'] ?? null, $data['rowsPerPage'] ?? 10, $data['page'] ?? 1, $this->requestFilters($data)),
            MetaDefinitionsFiledsResource::class,
        );
    }
}
