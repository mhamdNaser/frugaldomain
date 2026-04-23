<?php

namespace App\Modules\Core\Repositories\Eloquent;

use App\Modules\Core\Models\WebhookLog;
use App\Modules\Core\Repositories\Interfaces\WebhookLogsRepositoryInterface;

class FrontendWebhookLogsRepository implements WebhookLogsRepositoryInterface
{
    public function __construct(
        protected WebhookLog $model
    ) {}

    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1)
    {
        $rowsPerPage = max(1, min($rowsPerPage, 100));
        $page = max(1, $page);

        return $this->applyTenantScope($this->model->newQuery())
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('provider', 'like', "%{$search}%")
                        ->orWhere('topic', 'like', "%{$search}%")
                        ->orWhere('external_id', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('error_message', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate($rowsPerPage, ['*'], 'page', $page);
    }

    private function applyTenantScope($query)
    {
        $user = auth()->user();

        if (
            $user
            && method_exists($user, 'hasRole')
            && $user->hasRole('partner')
            && !$user->hasRole('admin')
        ) {
            $storeId = $user->store?->id;
            abort_if(!$storeId, 404, 'No store is linked to the authenticated user.');
            $query->where('store_id', $storeId);
        }

        return $query;
    }
}

