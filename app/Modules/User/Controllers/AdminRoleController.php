<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Requests\PermissionsRoles\StoreRequest;
use App\Modules\User\Resources\RoleResource;
use App\Modules\User\Repositories\Eloquent\AdminRoleRepository;
use Illuminate\Http\Request;

class AdminRoleController extends Controller
{
    protected $roles;

    public function __construct(AdminRoleRepository $roles)
    {
        $this->roles = $roles;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $rowsPerPage = $request->input('rowsPerPage', 10);
        $page = $request->input('page', 1);

        $result = $this->roles->getAllRoles($search, $rowsPerPage, $page);

        return response()->json([
            'data' => RoleResource::collection($result['data']),
            'meta' => $result['meta'],
            'links' => $result['links'],
        ]);
    }

    public function allRoles()
    {
        $roles = $this->roles->allRoles();
        return response()->json(RoleResource::collection($roles));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'array'
        ]);

        $role = $this->roles->createRole($data);
        return response()->json($role);
    }

    public function update(StoreRequest $request, $id)
    {
        $data = $request->validated();

        $this->roles->updateRole($id, $data);

        return response()->json(['message' => 'Role updated successfully.'], 201);
    }

    public function deleteRoleArray(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:roles,id',
        ]);
        $this->roles->deleteArray($request->ids);
        return response()->json(['message' => 'Roles deleted successfully']);
    }

    public function destroy($id)
    {
        $this->roles->deleteRole($id);
        return response()->json(['message' => 'Role deleted successfully']);
    }
}
