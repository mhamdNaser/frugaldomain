<?php

namespace App\Modules\Fulfillment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fulfillment\Repositories\Interfaces\FulfillmentOrderItemsRepositoryInterface;
use App\Modules\Fulfillment\Requests\FulfillmentOrderItemsIndexRequest;
use App\Modules\Fulfillment\Requests\UpdateFulfillmentOrderItemRequest;
use App\Modules\Fulfillment\Resources\FulfillmentOrderItemTableResource;

class FulfillmentOrderItemController extends Controller
{
    public function __construct(
        protected FulfillmentOrderItemsRepositoryInterface $repo
    ) {}

    public function index(FulfillmentOrderItemsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['fulfillment_order_id'] ?? null,
            $data['order_item_id'] ?? null,
        );

        return response()->json([
            'data' => FulfillmentOrderItemTableResource::collection($result->items()),
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
            'data' => new FulfillmentOrderItemTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateFulfillmentOrderItemRequest $request, $id)
    {
        return response()->json([
            'message' => 'Fulfillment order item updated successfully',
            'data' => new FulfillmentOrderItemTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
