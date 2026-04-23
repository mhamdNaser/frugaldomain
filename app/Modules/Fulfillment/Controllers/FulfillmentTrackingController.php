<?php

namespace App\Modules\Fulfillment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fulfillment\Repositories\Interfaces\FulfillmentTrackingRepositoryInterface;
use App\Modules\Fulfillment\Requests\FulfillmentTrackingIndexRequest;
use App\Modules\Fulfillment\Requests\UpdateFulfillmentTrackingRequest;
use App\Modules\Fulfillment\Resources\FulfillmentTrackingTableResource;

class FulfillmentTrackingController extends Controller
{
    public function __construct(
        protected FulfillmentTrackingRepositoryInterface $repo
    ) {}

    public function index(FulfillmentTrackingIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['fulfillment_id'] ?? null,
        );

        return response()->json([
            'data' => FulfillmentTrackingTableResource::collection($result->items()),
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
            'data' => new FulfillmentTrackingTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateFulfillmentTrackingRequest $request, $id)
    {
        return response()->json([
            'message' => 'Fulfillment tracking updated successfully',
            'data' => new FulfillmentTrackingTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
