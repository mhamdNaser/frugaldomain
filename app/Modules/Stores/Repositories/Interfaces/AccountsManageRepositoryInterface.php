<?php

namespace App\Modules\Stores\Repositories\Interfaces;

interface AccountsManageRepositoryInterface
{
    public function forPartner(string $storeId): array;
}

