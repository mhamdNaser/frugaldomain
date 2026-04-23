<?php

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Repositories\Interfaces\ProductsRepositoryInterface;
use App\Modules\Catalog\Requests\ProductIndexRequest;
use App\Modules\Catalog\Requests\UpdateProductRequest;
use App\Modules\Catalog\Resources\ProductDetailResource;
use App\Modules\Catalog\Resources\ProductTableResource;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    protected $repo;

    public function __construct(ProductsRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(ProductIndexRequest $request)
    {
        $data = $request->validated();
        $search = $data['search'] ?? null;
        $rowsPerPage = $data['rowsPerPage'] ?? 10;
        $page = $data['page'] ?? 1;

        $result = $this->repo->all($search, $rowsPerPage, $page);

        return response()->json([
            'data' => ProductTableResource::collection($result->items()),
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return response()->json([
            'data' => new ProductDetailResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, $id)
    {
        return response()->json([
            'message' => 'Product updated successfully',
            'data' => new ProductDetailResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }

    public function changeStatus($id)
    {
        $icon = $this->repo->toggleStatus($id);

        return response()->json([
            'success' => true,
            'message' => 'Status changed successfully',
            'data' => new ProductTableResource($icon)
        ]);
    }
}
