<?php

namespace App\Modules\Core\Repositories\Eloquent;

use App\Modules\Core\Repositories\Interfaces\DashboardRepositoryInterface;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\User;

class DashboardRepository implements DashboardRepositoryInterface
{
    protected $userModel;
    protected $storeModel;

    public function __construct(Store $store, User $user)
    {
        $this->userModel = $user;
        $this->storeModel = $store;
    }


    public function getTotalUsers(): int
    {
        return $this->userModel::count();
    }

    public function getActiveUsers(): int
    {
        return $this->userModel::where('status', 'active')
            ->orWhere('is_active', true)
            ->count();
    }

    public function getTotalStores(): int
    {
        return $this->storeModel::count();
    }

    public function getUsersWithStores(): int
    {
        return $this->userModel::has('store')->count();
    }

}
