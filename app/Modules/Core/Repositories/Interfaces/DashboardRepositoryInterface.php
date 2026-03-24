<?php

namespace App\Modules\Core\Repositories\Interfaces;

interface DashboardRepositoryInterface
{
    public function getTotalUsers(): int;
    public function getActiveUsers(): int;
    public function getTotalStores(): int;
    public function getUsersWithStores(): int;
}
