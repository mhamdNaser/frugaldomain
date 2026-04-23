<?php

namespace App\Modules\Fulfillment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fulfillment\Repositories\Interfaces\FulfillmentOrdersRepositoryInterface;
use App\Modules\Fulfillment\Requests\FulfillmentOrdersIndexRequest;
use App\Modules\Fulfillment\Requests\UpdateFulfillmentOrderRequest;
use App\Modules\Fulfillment\Resources\FulfillmentOrderTableResource;

class FulfillmentOrderController extends Controller
{
    public function __construct(
        protected FulfillmentOrdersRepositoryInterface $repo
    ) {}

    public function index(FulfillmentOrdersIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['order_id'] ?? null,
            $data['fulfillment_service_id'] ?? null,
        );

        return response()->json([
            'data' => FulfillmentOrderTableResource::collection($result->items()),
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
            'data' => new FulfillmentOrderTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateFulfillmentOrderRequest $request, $id)
    {
        return response()->json([
            'message' => 'Fulfillment order updated successfully',
            'data' => new FulfillmentOrderTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
