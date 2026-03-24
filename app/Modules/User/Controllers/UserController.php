<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Resources\UserResource;
use App\Modules\User\Repositories\Interfaces\UserRepositoryInterface;
use App\Modules\User\Requests\User\StoreUserRequest;
use App\Modules\User\Requests\User\UpdateUserRequest;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $repos;

    public function __construct(UserRepositoryInterface $users)
    {
        $this->repos = $users;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $rowsPerPage = $request->input('rowsPerPage', 10);
        $page = $request->input('page', 1);

        $result = $this->repos->getAllUsers($search, $rowsPerPage, $page);

        return response()->json([
            'data' => UserResource::collection($result['data']),
            'meta' => $result['meta'],
            'links' => $result['links'],
        ]);
    }


    public function all()
    {
        $data =  $this->repos->all();
        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $user = $this->repos->create($data);
        return response()
            ->json([
                'message' => 'User created successfully',
                'data' => new UserResource($user)
            ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = $this->repos->find($id);

        if ($user) {
            return response()->json(new UserResource($user));
        }

        return response()->json(['message' => 'User not found'], 404);
    }

    public function changStatus($id)
    {
        $user = $this->repos->toggleStatus($id);

        return response()->json([
            'message' => 'User Status Updated',
            'data' => $user
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, $id)
    {
        $data = $request->validated();
        $user = $this->repos->update($id, $data);

        if ($user) {
            return response()
                ->json([
                    'message' => 'User created successfully',
                    'data' => new UserResource($user)
                ], 201);
        }

        return response()->json(['message' => 'User not found'], 404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $deleted = $this->repos->delete($id);

        if ($deleted) {
            return response()->json(['message' => 'User deleted successfully'], 200);
        }

        return response()->json(['message' => 'User not found'], 404);
    }

    public function destroyArray(Request $request)
    {
        $ids = $request->input('ids', []);
        $deleted = $this->repos->deleteArray($ids);

        if ($deleted) {
            return response()->json(['message' => 'Users deleted successfully'], 200);
        }

        return response()->json(['message' => 'No users found to delete'], 404);
    }
}
