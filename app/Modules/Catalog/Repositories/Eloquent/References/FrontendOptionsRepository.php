<?php

namespace App\Modules\Catalog\Repositories\Eloquent\References;

use App\Modules\Catalog\Models\Option;
use App\Modules\Catalog\Models\OptionValue;
use App\Modules\Catalog\Repositories\Interfaces\References\OptionsRepositoryInterface;
use Illuminate\Support\Arr;

class FrontendOptionsRepository implements OptionsRepositoryInterface
{
    public function __construct(
        protected Option $model
    ) {}

    public function all($search = null, $rowsPerPage = 10, $page = 1)
    {
        $rowsPerPage = max(1, min((int) $rowsPerPage, 100));
        $page = max(1, (int) $page);

        return $this->applyTenantScope(Option::query())
            ->with(['values:id,option_id,label,value'])
            ->withCount('products')
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhereHas('values', function ($query) use ($search) {
                            $query->where('label', 'like', "%{$search}%")
                                ->orWhere('value', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate($rowsPerPage, ['*'], 'page', $page);
    }

    public function find(int $id)
    {
        return $this->applyTenantScope($this->model->newQuery())->findOrFail($id);
    }

    public function findForFrontend(int $id)
    {
        return $this->applyTenantScope(Option::query())
            ->with([
                'values:id,option_id,label,value',
                'products:id,title,handle,status',
            ])
            ->withCount('products')
            ->findOrFail($id);
    }

    public function create(array $data)
    {
        $values = Arr::pull($data, 'values', []);
        $option = $this->model->newQuery()->create($data);
        $this->syncValues($option, $values);

        return $this->findForFrontend((int) $option->id);
    }

    public function update(int $id, array $data)
    {
        $valuesProvided = Arr::exists($data, 'values');
        $values = Arr::pull($data, 'values', []);
        $option = $this->find($id);
        $option->fill($data);
        $option->save();
        if ($valuesProvided) {
            $this->syncValues($option, $values);
        }

        return $this->findForFrontend($option->id);
    }

    public function delete(int $id): void
    {
        $option = $this->find($id);
        $option->delete();
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

    private function syncValues(Option $option, array $values): void
    {
        $normalized = collect($values)
            ->filter(fn ($value) => is_array($value))
            ->map(fn ($value) => [
                'label' => isset($value['label']) ? trim((string) $value['label']) : null,
                'value' => isset($value['value']) ? trim((string) $value['value']) : null,
            ])
            ->filter(fn ($value) => $value['label'] && $value['value'])
            ->values();

        $option->values()->delete();

        if ($normalized->isEmpty()) {
            return;
        }

        $option->values()->createMany($normalized->all());
    }
}
