<?php

namespace App\Modules\Marketing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Marketing\Repositories\Interfaces\SellingPlanSubscriptionsRepositoryInterface;
use App\Modules\Marketing\Requests\SellingPlanSubscriptionsIndexRequest;
use App\Modules\Marketing\Resources\SellingPlanSubscriptionTableResource;

class SellingPlanSubscriptionController extends Controller
{
    public function __construct(
        protected SellingPlanSubscriptionsRepositoryInterface $repo
    ) {}

    public function index(SellingPlanSubscriptionsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            (int) ($data['rowsPerPage'] ?? 10),
            (int) ($data['page'] ?? 1),
            $data['customer_id'] ?? null,
        );

        return response()->json([
            'data' => SellingPlanSubscriptionTableResource::collection($result->items()),
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

