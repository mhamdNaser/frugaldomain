<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Repositories\Interfaces\SyncMonitorRepositoryInterface;
use App\Modules\Core\Requests\SyncMonitorIndexRequest;
use App\Modules\Core\Resources\SyncMonitorResource;

class SyncMonitorController extends Controller
{
    public function __construct(
        protected SyncMonitorRepositoryInterface $repo
    ) {}

    public function index(SyncMonitorIndexRequest $request, string $type)
    {
        abort_unless(in_array($type, $this->repo->allowedTypes(), true), 404, 'Unknown sync monitor type.');

        $data = $request->validated();
        $store = $request->user()?->store()->first();
        $result = $this->repo->all(
            type: $type,
            storeId: $store?->id,
            search: $data['search'] ?? null,
            rowsPerPage: (int) ($data['rowsPerPage'] ?? 10),
            page: (int) ($data['page'] ?? 1),
        );

        return response()->json([
            'data' => $result->getCollection()
                ->map(fn ($item) => (new SyncMonitorResource($item))->additional(['type' => $type])->toArray($request))
                ->values(),
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