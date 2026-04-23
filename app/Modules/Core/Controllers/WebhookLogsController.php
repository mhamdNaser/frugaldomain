<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Repositories\Interfaces\WebhookLogsRepositoryInterface;
use App\Modules\Core\Requests\WebhookLogsIndexRequest;
use App\Modules\Core\Resources\WebhookLogTableResource;

class WebhookLogsController extends Controller
{
    public function __construct(
        protected WebhookLogsRepositoryInterface $repo
    ) {}

    public function index(WebhookLogsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            (int) ($data['rowsPerPage'] ?? 10),
            (int) ($data['page'] ?? 1),
        );

        return response()->json([
            'data' => WebhookLogTableResource::collection($result->items()),
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

