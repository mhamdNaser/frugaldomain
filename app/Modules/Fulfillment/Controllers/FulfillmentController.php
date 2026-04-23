<?php

namespace App\Modules\Fulfillment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fulfillment\Repositories\Interfaces\FulfillmentsRepositoryInterface;
use App\Modules\Fulfillment\Requests\FulfillmentsIndexRequest;
use App\Modules\Fulfillment\Requests\UpdateFulfillmentRequest;
use App\Modules\Fulfillment\Resources\FulfillmentTableResource;

class FulfillmentController extends Controller
{
    public function __construct(
        protected FulfillmentsRepositoryInterface $repo
    ) {}

    public function index(FulfillmentsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['order_id'] ?? null,
        );

        return response()->json([
            'data' => FulfillmentTableResource::collection($result->items()),
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
            'data' => new FulfillmentTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateFulfillmentRequest $request, $id)
    {
        return response()->json([
            'message' => 'Fulfillment updated successfully',
            'data' => new FulfillmentTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
