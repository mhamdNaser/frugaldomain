<?php

namespace App\Modules\Billing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Repositories\Interfaces\SubscriptionsRepositoryInterface;
use App\Modules\Billing\Requests\SubscriptionsIndexRequest;
use App\Modules\Billing\Requests\UpdateSubscriptionRequest;
use App\Modules\Billing\Resources\SubscriptionTableResource;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionsRepositoryInterface $repo
    ) {}

    public function index(SubscriptionsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['store_id'] ?? null,
            $data['plan_id'] ?? null,
        );

        return response()->json([
            'data' => SubscriptionTableResource::collection($result->items()),
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

    public function show($id)
    {
        return response()->json([
            'data' => new SubscriptionTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateSubscriptionRequest $request, $id)
    {
        return response()->json([
            'message' => 'Subscription updated successfully',
            'data' => new SubscriptionTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
