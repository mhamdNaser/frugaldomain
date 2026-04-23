<?php

namespace App\Modules\Fulfillment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fulfillment\Repositories\Interfaces\FulfillmentItemsRepositoryInterface;
use App\Modules\Fulfillment\Requests\FulfillmentItemsIndexRequest;
use App\Modules\Fulfillment\Requests\UpdateFulfillmentItemRequest;
use App\Modules\Fulfillment\Resources\FulfillmentItemTableResource;

class FulfillmentItemController extends Controller
{
    public function __construct(
        protected FulfillmentItemsRepositoryInterface $repo
    ) {}

    public function index(FulfillmentItemsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['fulfillment_id'] ?? null,
            $data['order_item_id'] ?? null,
        );

        return response()->json([
            'data' => FulfillmentItemTableResource::collection($result->items()),
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
            'data' => new FulfillmentItemTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateFulfillmentItemRequest $request, $id)
    {
        return response()->json([
            'message' => 'Fulfillment item updated successfully',
            'data' => new FulfillmentItemTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
