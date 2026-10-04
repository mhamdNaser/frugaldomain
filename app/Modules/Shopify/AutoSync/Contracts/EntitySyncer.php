<?php

namespace App\Modules\Shopify\AutoSync\Contracts;

use App\Modules\Shopify\AutoSync\SyncContext;
use Illuminate\Database\Eloquent\Model;

/**
 * Pushes one local entity type to Shopify.
 */
interface EntitySyncer
{
    /**
     * State captured before an update/delete (old names, relation ids, Shopify ids...).
     *
     * @return array<string, mixed>
     */
    public function snapshot(Model $model): array;

    public function create(Model $model, SyncContext $ctx): void;

    /**
     * @param array<string, mixed> $before snapshot taken before the local change
     * @param array<int, string> $changed request keys that were changed
     */
    public function update(Model $model, array $before, array $changed, SyncContext $ctx): void;

    /**
     * @param array<string, mixed> $snapshot snapshot taken before the local delete
     */
    public function delete(array $snapshot, SyncContext $ctx): void;
}
