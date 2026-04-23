<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Repositories\Interfaces\FilesRepositoryInterface;
use App\Modules\CMS\Requests\FilesIndexRequest;
use App\Modules\CMS\Resources\FileTableResource;

class FileController extends Controller
{
    public function __construct(
        protected FilesRepositoryInterface $repo
    ) {}

    public function index(FilesIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 15,
            $data['page'] ?? 1,
            [
                'parent_field' => $data['parent_field'] ?? null,
                'parent_id' => $data['parent_id'] ?? null,
                'file_type' => $data['file_type'] ?? null,
                'role' => $data['role'] ?? null,
                'owner_type' => $data['owner_type'] ?? null,
                'sort_by' => $data['sort_by'] ?? null,
                'sort_direction' => $data['sort_direction'] ?? 'desc',
            ],
        );

        return response()->json([
            'data' => FileTableResource::collection($result->items()),
            'links' => [
                'first' => $result->url(1),
                'last' => $result->url($result->lastPage()),
                'prev' => $result->previousPageUrl(),
                'next' => $result->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $result->currentPage(),
                'from' => $result->firstItem(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'to' => $result->lastItem(),
                'total' => $result->total(),
            ],
            'facets' => $this->repo->facets(),
        ]);
    }
}
