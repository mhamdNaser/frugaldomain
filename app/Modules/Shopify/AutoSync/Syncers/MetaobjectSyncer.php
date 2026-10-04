<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

use App\Modules\CMS\Models\MetaObject;
use App\Modules\Shopify\AutoSync\Support\Gid;
use App\Modules\Shopify\AutoSync\SyncContext;
use Illuminate\Database\Eloquent\Model;

class MetaobjectSyncer extends BaseSyncer
{
    /**
     * @param MetaObject $model
     */
    public function create(Model $model, SyncContext $ctx): void
    {
        if (Gid::make($model->shopify_metaobject_id, 'Metaobject')) {
            $this->update($model, [], ['fields'], $ctx);
            return;
        }

        $result = $ctx->gw->mutate(<<<'GQL'
mutation MetaobjectCreate($metaobject: MetaobjectCreateInput!) {
  metaobjectCreate(metaobject: $metaobject) {
    metaobject { id }
    userErrors { field message code }
  }
}
GQL, ['metaobject' => [
            'type' => (string) $model->type,
            'fields' => $this->fields($model->fields),
        ]], 'metaobjectCreate');

        $this->persist($model, ['shopify_metaobject_id' => $result['metaobject']['id'] ?? null]);
    }

    /**
     * @param MetaObject $model
     */
    public function update(Model $model, array $before, array $changed, SyncContext $ctx): void
    {
        $gid = Gid::make($model->shopify_metaobject_id, 'Metaobject');

        if (!$gid) {
            $this->create($model, $ctx);
            return;
        }

        if (!in_array('fields', $changed, true)) {
            return;
        }

        $ctx->gw->mutate(<<<'GQL'
mutation MetaobjectUpdate($id: ID!, $metaobject: MetaobjectUpdateInput!) {
  metaobjectUpdate(id: $id, metaobject: $metaobject) {
    metaobject { id }
    userErrors { field message code }
  }
}
GQL, ['id' => $gid, 'metaobject' => ['fields' => $this->fields($model->fields)]], 'metaobjectUpdate');
    }

    public function delete(array $snapshot, SyncContext $ctx): void
    {
        $gid = Gid::make($snapshot['shopify_metaobject_id'] ?? null, 'Metaobject');

        if ($gid) {
            $this->ignoreNotFound(fn () => $ctx->gw->mutate(
                'mutation MetaobjectDelete($id: ID!) { metaobjectDelete(id: $id) { deletedId userErrors { field message code } } }',
                ['id' => $gid],
                'metaobjectDelete',
            ));
        }
    }

    /**
     * Accepts both the Shopify shape ([{key, value}, ...]) and a plain key => value map.
     *
     * @return array<int, array{key: string, value: string}>
     */
    private function fields(mixed $fields): array
    {
        if (is_string($fields)) {
            $fields = json_decode($fields, true);
        }

        if (!is_array($fields)) {
            return [];
        }

        $isList = array_is_list($fields) && collect($fields)->every(fn ($field) => is_array($field) && isset($field['key']));
        $pairs = $isList
            ? collect($fields)->mapWithKeys(fn ($field) => [(string) $field['key'] => $field['value'] ?? null])
            : collect($fields);

        return $pairs
            ->map(fn ($value, $key) => [
                'key' => (string) $key,
                'value' => is_array($value) ? (string) json_encode($value, JSON_UNESCAPED_UNICODE) : (string) ($value ?? ''),
            ])
            ->values()
            ->all();
    }
}
