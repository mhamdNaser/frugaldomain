<?php

namespace App\Modules\Stores\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stores\Resources\AccountsManageResource;
use App\Modules\Stores\Services\AccountsManageService;
use Illuminate\Http\Request;

class AccountsManageController extends Controller
{
    public function __construct(
        private readonly AccountsManageService $service
    ) {}

    public function show(Request $request): AccountsManageResource
    {
        return new AccountsManageResource($this->service->forUser($request->user()));
    }
}

