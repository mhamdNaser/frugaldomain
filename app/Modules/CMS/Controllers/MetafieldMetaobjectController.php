<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Controllers\Concerns\RespondsWithCmsPaginator;
use App\Modules\CMS\Repositories\Interfaces\MetafieldMetaobjectsRepositoryInterface;
use App\Modules\CMS\Requests\MetafieldMetaobjectsIndexRequest;
use App\Modules\CMS\Resources\MetafieldMetaobjectTableResource;

class MetafieldMetaobjectController extends Controller
{
    use RespondsWithCmsPaginator;

    public function __construct(protected MetafieldMetaobjectsRepositoryInterface $repo) {}

    public function index(MetafieldMetaobjectsIndexRequest $request)
    {
        $data = $request->validated();

        return $this->paginatedResponse(
            $this->repo->all($data['search'] ?? null, $data['rowsPerPage'] ?? 10, $data['page'] ?? 1, $this->requestFilters($data)),
            MetafieldMetaobjectTableResource::class,
        );
    }
}
