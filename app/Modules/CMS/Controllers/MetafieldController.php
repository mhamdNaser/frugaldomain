<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Controllers\Concerns\RespondsWithCmsPaginator;
use App\Modules\CMS\Repositories\Interfaces\MetafieldsRepositoryInterface;
use App\Modules\CMS\Requests\MetafieldsIndexRequest;
use App\Modules\CMS\Resources\MetafieldTableResource;

class MetafieldController extends Controller
{
    use RespondsWithCmsPaginator;

    public function __construct(protected MetafieldsRepositoryInterface $repo) {}

    public function index(MetafieldsIndexRequest $request)
    {
        $data = $request->validated();

        return $this->paginatedResponse(
            $this->repo->all($data['search'] ?? null, $data['rowsPerPage'] ?? 10, $data['page'] ?? 1, $this->requestFilters($data)),
            MetafieldTableResource::class,
        );
    }
}
