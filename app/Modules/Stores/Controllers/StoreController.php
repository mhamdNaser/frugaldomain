<?php

namespace App\Modules\Stores\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stores\Repositories\Interfaces\StoreRepositoryInterface;
use App\Modules\Stores\Requests\CreateStoreRequest;
use App\Modules\Stores\Requests\UpdateStoreRequest;
use App\Modules\Stores\Resources\StoreResource;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    protected $store;

    public function __construct(StoreRepositoryInterface $data)
    {
        $this->store = $data;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $rowsPerPage = $request->input('rowsPerPage', 10);
        $page = $request->input('page', 1);

        $result = $this->store->all($search, $rowsPerPage, $page);

        return response()->json([
            'data' => StoreResource::collection($result['data']),
            'meta' => $result['meta'],
            'links' => $result['links'],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateStoreRequest $request)
    {
        $data = $request->validated();
        $store = $this->store->create($data);
        return response()
            ->json([
                'message' => 'Store created successfully',
                'data' => new StoreResource($store)
            ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $store = $this->store->find($id);
        if ($store) {
            return response()->json(new StoreResource($store));
        }

        return response()->json(['message' => 'Store not found'], 404);
    }

    public function changStatus($id){
        $store = $this->store->toggleStatus($id);

        return response()->json([
            'message'=> 'Store Status Updated',
            'data'=> $store
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStoreRequest $request, $id)
    {
        $data = $request->validated();
        $store = $this->store->update($id, $data);

        if ($store) {
            return response()
                ->json([
                    'message' => 'Store created successfully',
                    'data' => new StoreResource($store)
                ], 201);
        }

        return response()->json(['message' => 'Store not found'], 404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $deleted = $this->store->delete($id);

        if ($deleted) {
            return response()->json(['message' => 'Store deleted successfully'], 200);
        }

        return response()->json(['message' => 'Store not found'], 404);
    }
}
