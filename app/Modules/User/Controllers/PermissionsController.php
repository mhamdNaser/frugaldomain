<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Requests\PermissionsRoles\StoreRequest;
use App\Modules\User\Repositories\Eloquent\PermissionsRepository;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionsController extends Controller
{
    protected $permissions;

    public function __construct(PermissionsRepository $permissions)
    {
        $this->permissions = $permissions;
    }

    // Permissions management
    public function index(Request $request)
    {
        $search = $request->input('search');
        $rowsPerPage = $request->input('rowsPerPage', 10);
        $page = $request->input('page', 1);

        $result = $this->permissions->getAllPermissions($search, $rowsPerPage, $page);

        return response()->json([
            'data' => $result['data'],
            'meta' => $result['meta'],
            'links' => $result['links'],
        ]);
    }

    public function allPermissions()
    {
        $permissions = $this->permissions->all();
        return response()->json($permissions);
    }

    public function store(Request $request)
    {
        // Create a new permission
        $data = $request->validate([
            'name' => 'required|string|unique:permissions,name',
        ]);

        $permission = $this->permissions->create($data);
        return response()->json($permission);
    }

    public function update(StoreRequest $request, $id)
    {
        // Update an existing permission
        $data = $request->validated();

        $permission = $this->permissions->update($id, $data);

        return response()->json(['message' => 'permission updated successfully.'], 201);
    }

    public function updateRolePermissions(Request $request, $roleId)
    {
        try {
            // 1. استرجاع البيانات من FormData
            $permissionsData = json_decode($request->input('permission')[0], true);

            if (!$permissionsData || !is_array($permissionsData)) {
                return response()->json(['success' => false, 'message' => 'Invalid permission format.'], 400);
            }

            $truePermissions = array_filter($permissionsData, function ($perm) {
                return $perm['value'] === true;
            });

            $truePermissions = array_map(function ($perm) {
                return trim($perm['id']);
            }, $truePermissions);

            $truePermissions = array_values($truePermissions);

            // 3. جلب IDs الخاصة بالبيرمشنات المفعلة
            $permissionIds = Permission::whereIn('name', $truePermissions)->pluck('id')->toArray();

            // 4. استدعاء الريبو لتحديث الصلاحيات
            $this->permissions->updateRolePermissions($roleId, $permissionIds);


            return response()->json(['message' => 'Role permissions updated successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating permissions: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function destroy($id)
    {
        // Delete a permission
        $this->permissions->delete($id);
        return response()->json(['message' => 'Permission deleted successfully']);
    }

    public function destroyArray(Request $request)
    {
        $ids = $request->input('ids');
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['message' => 'Invalid input. Please provide an array of IDs.'], 400);
        }

        $this->permissions->deleteArray($ids);
        return response()->json(['message' => 'Permissions deleted successfully']);
    }
}
