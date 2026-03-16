<?php

namespace App\Modules\Locale\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Locale\Repositories\Interfaces\StateRepositoryInterface;
use App\Modules\Locale\Resources\StatesResource;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StateController extends Controller
{
    protected $repos;

    public function __construct(StateRepositoryInterface $stateRepository)
    {
        $this->repos = $stateRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        $states = $this->repos->getByCountryId($id);
        return StatesResource::collection($states);
    }

    public function allstates(Request $request, $id)
    {

        $search = $request->get('search');
        $rowsPerPage = $request->get('rowsPerPage', 10);
        $page = $request->get('page', 1);

        $states = $this->repos->getByCountryId($id);

        $filteredstate = $states->filter(function ($state) use ($search) {
            return strpos(strtolower($state->name), strtolower($search)) !== false;
        });


        $currentPage = $page;
        $perPage = $rowsPerPage;
        $offset = ($currentPage - 1) * $perPage;

        $pagedstate = $filteredstate->slice($offset, $perPage)->values();


        $paginator = new LengthAwarePaginator(
            $pagedstate,
            $filteredstate->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'data' => StatesResource::collection($paginator->items()),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $this->repos->delete($id);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'State deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete state.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroyarray(Request $request)
    {
        $validatedData = $request->validate([
            'array' => 'required|array',
        ]);

        $idsToDelete = $validatedData['array'];

        DB::beginTransaction();

        try {
            $this->repos->deleteMany($idsToDelete);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'States deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete states.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
