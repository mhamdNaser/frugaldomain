<?php

namespace App\Modules\Icon\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Icon\Resources\IconCategoryResource;
use App\Modules\Icon\Repositories\Interfaces\IconCategoryRepositoryInterface;
use App\Modules\Icon\Requests\IconCategoryRequest;
use Illuminate\Http\Request;

class IconCategoriesController extends Controller
{
    protected $repo;

    public function __construct(IconCategoryRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $rowsPerPage = $request->input('rowsPerPage', 10);
        $page = $request->input('page', 1);

        $result = $this->repo->all($search, $rowsPerPage, $page);

        return response()->json([
            'data' => IconCategoryResource::collection($result['data']),
            'meta' => $result['meta'],
            'links' => $result['links'],
        ]);
    }

    public function allWithoutPagination()
    {
        $categories = $this->repo->allWithoutPagination();
        return response()->json($categories);
    }

    public function store(IconCategoryRequest $request)
    {
        $category = $this->repo->create($request->validated());
        return response()->json(['message' => 'Icon created successfully'], 201);
    }

    public function update(IconCategoryRequest $request, $id)
    {
        $category = $this->repo->update($id, $request->validated());
        return new IconCategoryResource($category);
    }


    public function destroy($id)
    {
        $this->repo->delete($id);
        return response()->json(['success' => true, 'message' => 'Category deleted successfully.']);
    }

    public function destroyArray(Request $request)
    {
        $ids = $request->input('ids', []);

        if (is_string($ids)) {
            $ids = array_filter(array_map('trim', explode(',', $ids)));
        }

        $ids = array_values(array_filter($ids, fn($v) => $v !== null && $v !== ''));

        $deleted = $this->repo->deleteArray($ids);

        return response()->json([
            'requested_ids' => $ids,
            'deleted' => $deleted,
            'message' => $deleted > 0 ? 'Categories deleted successfully.' : 'No categories were deleted.',
        ], $deleted > 0 ? 200 : 404);

        // $result = $this->repo->deleteArray($ids);
        // if ($result) {
        //     return response()->json(['message' => 'Categories deleted successfully.'], 200);
        // }
        // return response()->json(['message' => 'Failed to delete categories.'], 500);
    }

    public function changeStatus($id)
    {
        $category = $this->repo->changeStatus($id);
        return new IconCategoryResource($category);
    }
}
