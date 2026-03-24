<?php

namespace App\Modules\User\Repositories\Eloquent;

use App\Modules\User\Models\User;
use App\Modules\User\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use App\Traits\PaginatesCollection;
use App\Traits\ManageFiles;
use Spatie\Permission\Models\Role;

class UserRepository implements UserRepositoryInterface
{
    use PaginatesCollection;
    use ManageFiles;

    protected $model;
    protected $cacheKey;

    public function __construct(User $user)
    {
        $this->model = $user;
        $this->cacheKey = "all_Users";
    }

    public function getAllUsers($search = null, $rowsPerPage = 10, $page = 1)
    {


        $items = Cache::remember($this->cacheKey, 60, function () {
            return $this->model::orderBy('id', 'desc')->get();
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
        return $this->model::select('id', 'name')->get();
    }

    public function find($id)
    {
        return $this->model::find($id);
    }

    public function create($data)
    {
        Cache::forget($this->cacheKey);

        $roleId = $data['role_id'] ?? null;
        unset($data['role_id']);

        $data['password'] = Hash::make($data['password']);
        $svgPath = $this->uploadImage($data['image'], 'users', $data['name']);
        $data['image'] = $svgPath;

        $user = $this->model::create($data);

        if ($roleId) {
            $role = Role::find($roleId);
            $user->assignRole($role);
        }
        return $user;
    }

    public function update($id, array $data)
    {
        Cache::forget($this->cacheKey);
        $user = $this->find($id);

        $roleId = $data['role_id'] ?? null;
        unset($data['role_id']);
        unset($data['image']);

        if (array_key_exists('password', $data)) {
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = Hash::make($data['password']);
            }
        }


        $user->update($data);

        if ($roleId) {
            $role = Role::find($roleId);
            if ($role) {
                $user->syncRoles([$role]);
            }
        }

        return $user;
    }

    public function toggleStatus($id)
    {
        $user = $this->find($id);
        Cache::forget($this->cacheKey);
        $user->status = !$user->status;

        $user->save();
        return $user;
    }

    public function delete($id): bool
    {
        Cache::forget($this->cacheKey);
        $user = $this->find($id);
        if ($user) {
            return $user->delete();
        }
        return false;
    }

    public function deleteArray(array $ids): bool
    {
        Cache::forget($this->cacheKey);
        return $this->model::whereIn('id', $ids)->delete();
    }
}
