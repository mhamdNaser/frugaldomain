<?php

namespace App\Modules\Stores\Repositories\Eloquent;

use App\Modules\Stores\Models\Store;
use App\Modules\Stores\Repositories\Interfaces\StoreRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use App\Traits\PaginatesCollection;
use App\Traits\ManageFiles;
use Illuminate\Support\Facades\Crypt;
use Spatie\Permission\Models\Role;

class StoreRepository implements StoreRepositoryInterface
{
    use PaginatesCollection;
    use ManageFiles;

    protected $model;
    protected $cacheKey;

    public function __construct(Store $Store)
    {
        $this->model = $Store;
        $this->cacheKey = "all_Stores";
    }

    public function all($search = null, $rowsPerPage = 10, $page = 1)
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

    public function find($id)
    {
        return $this->model::find($id);
    }

    public function create($data)
    {
        Cache::forget($this->cacheKey);

        $data['shopify_access_token'] = Crypt::encryptString($data['shopify_access_token']);

        $Store = $this->model::create($data);
        return $Store;
    }

    public function update($id, array $data)
    {
        Cache::forget($this->cacheKey);
        $Store = $this->find($id);

        $roleId = $data['role_id'] ?? null;
        unset($data['role_id']);
        unset($data['image']);

        $data['shopify_access_token'] = Crypt::encryptString($data['shopify_access_token']);

        if (array_key_exists('password', $data)) {
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = Hash::make($data['password']);
            }
        }

        if (array_key_exists('shopify_access_token', $data)) {
            if (filled($data['shopify_access_token'])) {
                $data['shopify_access_token'] = Crypt::encryptString($data['shopify_access_token']);
            } else {
                unset($data['shopify_access_token']);
            }
        }


        $Store->update($data);

        if ($roleId) {
            $role = Role::find($roleId);
            if ($role) {
                $Store->syncRoles([$role]);
            }
        }

        return $Store;
    }

    public function toggleStatus($id)
    {
        $Store = $this->find($id);
        Cache::forget($this->cacheKey);
        $Store->status = !$Store->status;

        $Store->save();
        return $Store;
    }

    public function delete($id): bool
    {
        Cache::forget($this->cacheKey);
        $Store = $this->find($id);
        if ($Store) {
            return $Store->delete();
        }
        return false;
    }
}
