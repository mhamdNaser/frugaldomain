<?php

namespace App\Modules\Stores\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stores\Repositories\Interfaces\StoreSettingsRepositoryInterface;
use App\Modules\Stores\Requests\StoreSettingsIndexRequest;
use App\Modules\Stores\Resources\StoreSettingTableResource;

class StoreSettingController extends Controller
{
    public function __construct(
        protected StoreSettingsRepositoryInterface $repo
    ) {}

    public function index(StoreSettingsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            (int) ($data['rowsPerPage'] ?? 10),
            (int) ($data['page'] ?? 1),
            $data['store_id'] ?? null,
        );

        return response()->json([
            'data' => StoreSettingTableResource::collection($result->items()),
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
