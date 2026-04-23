<?php

namespace App\Modules\CMS\Repositories\Eloquent;

use App\Modules\CMS\Models\File;
use App\Modules\CMS\Repositories\Interfaces\FilesRepositoryInterface;

class FrontendFilesRepository implements FilesRepositoryInterface
{
    public function __construct(
        protected File $model
    ) {}

    public function all(?string $search = null, int $rowsPerPage = 15, int $page = 1, array $filters = [])
    {
        $rowsPerPage = max(1, min($rowsPerPage, 100));
        $page = max(1, $page);
        $sortBy = $this->sortColumn($filters['sort_by'] ?? null);
        $sortDirection = strtolower((string) ($filters['sort_direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        return $this->applyTenantScope($this->model->newQuery())
            ->when($filters['parent_field'] ?? null, fn ($query, $field) => $this->applyParentFilter($query, $field, $filters['parent_id'] ?? null))
            ->when($filters['file_type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->when($filters['owner_type'] ?? null, fn ($query, $ownerType) => $query->where('fileable_type', $ownerType))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('fileable_type', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%")
                        ->orWhere('altText', 'like', "%{$search}%")
                        ->orWhere('mime_type', 'like', "%{$search}%");
                });
            })
            ->orderBy($sortBy, $sortDirection)
            ->orderByDesc('id')
            ->paginate($rowsPerPage, ['*'], 'page', $page);
    }

    public function facets(): array
    {
        return [
            'types' => $this->distinctValues('type'),
            'roles' => $this->distinctValues('role'),
            'owners' => $this->distinctValues('fileable_type'),
        ];
    }

    private function applyParentFilter($query, string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!in_array($field, ['fileable_id', 'fileable_type', 'store_id'], true)) {
            return;
        }

        $query->where($field, $value);
    }

    private function sortColumn(?string $column): string
    {
        return in_array($column, ['created_at', 'size', 'position', 'width', 'height'], true)
            ? $column
            : 'created_at';
    }

    private function distinctValues(string $column): array
    {
        return $this->applyTenantScope($this->model->newQuery())
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->values()
            ->all();
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
