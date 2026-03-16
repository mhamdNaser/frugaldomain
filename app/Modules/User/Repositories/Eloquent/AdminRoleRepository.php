<?php

namespace App\Modules\User\Repositories\Eloquent;

use App\Modules\User\Repositories\Interfaces\AdminRoleRepositoryInterface;
use App\Traits\PaginatesCollection;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

class AdminRoleRepository implements AdminRoleRepositoryInterface
{
    use PaginatesCollection;

    protected $model;

    public function __construct(Role $role)
    {
        $this->model = $role;
    }

    public function getAllRoles($search = null, $rowsPerPage = 10, $page = 1)
    {
        $cacheKey = "all_roles";

        $items = Cache::remember($cacheKey, 60, function () {
            return $this->model::orderBy('id', 'desc')->with('permissions')->get();
        });

        if ($search) {
            $items = $items->filter(function ($item) use ($search) {
                return stripos($item->name, $search) !== false;
            });
        }
        return $this->paginate($items, $rowsPerPage, $page);
    }

    public function allRoles()
    {
        return $this->model::where('name', '!=', 'super-admin')->get();
    }

    public function createRole(array $data)
    {
        Cache::forget('all_roles');
        $role = $this->model::create(['name' => $data['name'], 'guard_name' => $data['guard_name'] ?? 'web']);
        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }
        return $role;
    }


    public function updateRole($id, array $data)
    {
        Cache::forget('all_roles');
        $role = $this->model::find($id);
        $role->update($data);
    }

    public function deleteArray(array $ids)
    {
        $this->model->whereIn('id', $ids)->delete();
        Cache::flush();
    }

    public function deleteRole($id)
    {
        $role = $this->model::findOrFail($id);
        $role->delete();
        Cache::flush();
    }


    public function updateRolePermissions($id, array $permissions)
    {
        $role = $this->model::findOrFail($id);
        $role->syncPermissions($permissions);
        Cache::flush();
        return $role;
    }
}
