<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Repositories\Interfaces\WebhookSubscriptionsRepositoryInterface;
use App\Modules\Core\Requests\WebhookSubscriptionsIndexRequest;
use App\Modules\Core\Resources\WebhookSubscriptionTableResource;

class WebhookSubscriptionsController extends Controller
{
    public function __construct(
        protected WebhookSubscriptionsRepositoryInterface $repo
    ) {}

    public function index(WebhookSubscriptionsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            (int) ($data['rowsPerPage'] ?? 10),
            (int) ($data['page'] ?? 1),
        );

        return response()->json([
            'data' => WebhookSubscriptionTableResource::collection($result->items()),
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

