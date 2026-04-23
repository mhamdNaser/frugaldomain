<?php

namespace App\Modules\Orders\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Repositories\Interfaces\CartItemsRepositoryInterface;
use App\Modules\Orders\Requests\CartItemsIndexRequest;
use App\Modules\Orders\Requests\UpdateCartItemRequest;
use App\Modules\Orders\Resources\CartItemTableResource;

class CartItemController extends Controller
{
    public function __construct(
        protected CartItemsRepositoryInterface $repo
    ) {}

    public function index(CartItemsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['cart_id'] ?? null,
        );

        return response()->json([
            'data' => CartItemTableResource::collection($result->items()),
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
            'data' => new CartItemTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateCartItemRequest $request, $id)
    {
        return response()->json([
            'message' => 'Cart item updated successfully',
            'data' => new CartItemTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }
}
