<?php

namespace App\Modules\Fulfillment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fulfillment\Repositories\Interfaces\ReverseFulfillmentsRepositoryInterface;
use App\Modules\Fulfillment\Requests\ReverseFulfillmentsIndexRequest;
use App\Modules\Fulfillment\Requests\UpdateReverseFulfillmentRequest;
use App\Modules\Fulfillment\Resources\ReverseFulfillmentTableResource;

class ReverseFulfillmentController extends Controller
{
    public function __construct(
        protected ReverseFulfillmentsRepositoryInterface $repo
    ) {}

    public function index(ReverseFulfillmentsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['order_return_id'] ?? null,
        );

        return response()->json([
            'data' => ReverseFulfillmentTableResource::collection($result->items()),
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
            'data' => new ReverseFulfillmentTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateReverseFulfillmentRequest $request, $id)
    {
        return response()->json([
            'message' => 'Reverse fulfillment updated successfully',
            'data' => new ReverseFulfillmentTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
