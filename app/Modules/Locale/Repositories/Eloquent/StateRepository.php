<?php

namespace App\Modules\Locale\Repositories\Eloquent;

use App\Modules\Locale\Repositories\Interfaces\StateRepositoryInterface;
use App\Modules\Locale\Models\State;
use Illuminate\Database\Eloquent\Collection;

class StateRepository implements StateRepositoryInterface
{
    protected $model;

    public function __construct(State $state)
    {
        $this->model = $state;
    }

    public function getByCountryId($id): Collection
    {
        return State::query()
            ->select('id', 'name', 'country_id')
            ->where('country_id', $id)
            ->orderBy('name')
            ->get();
    }

    public function find($id): State
    {
        return $this->model->findOrFail($id);
    }

    public function delete($id): bool
    {
        $state = $this->find($id);
        return $state->delete();
    }

    public function deleteMany(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }
}
