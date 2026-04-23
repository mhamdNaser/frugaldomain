<?php

namespace App\Modules\Fulfillment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fulfillment\Repositories\Interfaces\FulfillmentServicesRepositoryInterface;
use App\Modules\Fulfillment\Requests\FulfillmentServicesIndexRequest;
use App\Modules\Fulfillment\Requests\UpdateFulfillmentServiceRequest;
use App\Modules\Fulfillment\Resources\FulfillmentServiceTableResource;

class FulfillmentServiceController extends Controller
{
    public function __construct(
        protected FulfillmentServicesRepositoryInterface $repo
    ) {}

    public function index(FulfillmentServicesIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
        );

        return response()->json([
            'data' => FulfillmentServiceTableResource::collection($result->items()),
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
            'data' => new FulfillmentServiceTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateFulfillmentServiceRequest $request, $id)
    {
        return response()->json([
            'message' => 'Fulfillment service updated successfully',
            'data' => new FulfillmentServiceTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
