<?php

namespace App\Modules\User\Repositories\Eloquent;

use App\Modules\User\Repositories\Interfaces\PermissionsRepositoryInterface;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Cache;
use App\Traits\PaginatesCollection;
use Spatie\Permission\Models\Role;

class PermissionsRepository implements PermissionsRepositoryInterface
{
    use PaginatesCollection;

    protected $model;

    public function __construct(Permission $permission)
    {
        $this->model = $permission;
    }

    public function getAllPermissions($search = null, $rowsPerPage = 10, $page = 1)
    {
        $cacheKey = "all_permissions";

        $items = Cache::remember($cacheKey, 60, function () {
            return $this->model->orderBy('id', 'desc')->get();
        });

        if ($search) {
            $items = $items->filter(function ($item) use ($search) {
                return stripos($item->name, $search) !== false;
            });
        }
        return $this->paginate($items, $rowsPerPage, $page);
    }

    public function all()
    {
        return $this->model::all();
    }

    public function create(array $data)
    {
        Cache::forget('all_permissions');

        $permission = $this->model::create(['name' => $data['name'], 'guard_name' => $data['guard_name'] ?? 'web']);

        return $permission;
    }

    public function update($id, array $data)
    {
        Cache::forget('all_permissions');

        $permission = $this->model::find($id);

        $permission->update($data);
    }

    public function updateRolePermissions($roleId, array $permissionIds)
    {
        $role = Role::findOrFail($roleId);
        $permissions = $this->model::whereIn('id', $permissionIds)->get();

        $role->syncPermissions($permissions);

        Cache::flush();
    }

    public function deleteArray(array $ids)
    {
        Cache::forget('all_permissions');
        return $this->model->destroy($ids);
    }

    public function delete($id)
    {
        Cache::forget('all_permissions');
        $permission = $this->model::findOrFail($id);
        return $permission->delete();
    }
}
