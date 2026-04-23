<?php

namespace App\Modules\Billing\Repositories\Eloquent;

use App\Modules\Billing\Models\Plan;
use App\Modules\Billing\Repositories\Interfaces\PlansRepositoryInterface;

class FrontendPlansRepository implements PlansRepositoryInterface
{
    public function __construct(
        protected Plan $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return Plan::query()
            ->withCount('subscriptions')
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('billing_interval', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($rowsPerPage, ['*'], 'page', $page);
    }

    public function find(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function findForFrontend(int $id)
    {
        return Plan::query()
            ->with('subscriptions')
            ->withCount('subscriptions')
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $plan = $this->find($id);
        $plan->fill($data);
        $plan->save();

        return $this->findForFrontend($plan->id);
    }

    public function toggleStatus(int $id)
    {
        $plan = $this->find($id);
        $plan->is_active = !$plan->is_active;
        $plan->save();

        return $plan;
    }
}
