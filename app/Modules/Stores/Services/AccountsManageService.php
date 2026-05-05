<?php

namespace App\Modules\Stores\Services;

use App\Modules\Stores\Repositories\Interfaces\AccountsManageRepositoryInterface;
use App\Modules\User\Models\User;

class AccountsManageService
{
    public function __construct(
        private readonly AccountsManageRepositoryInterface $repository
    ) {}

    public function forUser(User $user): array
    {
        $storeId = (string) ($user->store?->id ?? '');

        if ($storeId === '') {
            return [
                'store' => null,
                'settings' => null,
                'branding' => null,
                'shopify' => null,
            ];
        }

        return $this->repository->forPartner($storeId);
    }
}

