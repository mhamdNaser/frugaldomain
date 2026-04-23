<?php

namespace App\Modules\Stores\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stores\Repositories\Interfaces\StoreBrandingsRepositoryInterface;
use App\Modules\Stores\Requests\StoreBrandingsIndexRequest;
use App\Modules\Stores\Resources\StoreBrandingTableResource;

class StoreBrandingController extends Controller
{
    public function __construct(
        protected StoreBrandingsRepositoryInterface $repo
    ) {}

    public function index(StoreBrandingsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            (int) ($data['rowsPerPage'] ?? 10),
            (int) ($data['page'] ?? 1),
            $data['store_id'] ?? null,
        );

        return response()->json([
            'data' => StoreBrandingTableResource::collection($result->items()),
            'meta' => [
                'total' => $result->total(),
                'per_page' => $result->perPage(),
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'from' => $result->firstItem(),
                'to' => $result->lastItem(),
            ],
            'links' => [
                'first' => $result->url(1),
                'last' => $result->url($result->lastPage()),
                'prev' => $result->previousPageUrl(),
                'next' => $result->nextPageUrl(),
            ],
        ]);
    }
}
