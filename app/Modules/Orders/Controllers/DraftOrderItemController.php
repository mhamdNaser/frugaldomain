<?php

namespace App\Modules\Orders\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Repositories\Interfaces\DraftOrderItemsRepositoryInterface;
use App\Modules\Orders\Requests\DraftOrderItemsIndexRequest;
use App\Modules\Orders\Requests\UpdateDraftOrderItemRequest;
use App\Modules\Orders\Resources\DraftOrderItemTableResource;

class DraftOrderItemController extends Controller
{
    public function __construct(
        protected DraftOrderItemsRepositoryInterface $repo
    ) {}

    public function index(DraftOrderItemsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['draft_order_id'] ?? null,
        );

        return response()->json([
            'data' => DraftOrderItemTableResource::collection($result->items()),
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
            'data' => new DraftOrderItemTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateDraftOrderItemRequest $request, $id)
    {
        return response()->json([
            'message' => 'Draft order item updated successfully',
            'data' => new DraftOrderItemTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
