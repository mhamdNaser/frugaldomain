<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\CMS\Models\Metafield;
use App\Modules\CMS\Models\MetaObject;
use App\Modules\Shopify\DTOs\MetafieldsData;
use App\Modules\Stores\Models\Store;
use Illuminate\Database\Eloquent\Model;

class MetafieldSyncService
{
    /**
     * مزامنة الميتافيلدز مع أي نموذج (Product, Collection, Variant, Vendor)
     *
     * @param Store $store
     * @param Model $metafieldableModel
     * @param array<int, array> $metafields
     * @param bool $replaceOld
     */
    public function sync(Store $store, Model $model, array $metafields, bool $replaceOld = true): void
    {
        $metafieldableType = $model->getMorphClass();

        if ($replaceOld) {
            Metafield::whereIn('metafieldable_type', [$metafieldableType, get_class($model)])
                ->where('metafieldable_id', $model->id)
                ->delete();
        }

        foreach ($metafields as $metafield) {
            $this->upsertMetafield($store, $model, new MetafieldsData(...$metafield));
        }
    }

    private function upsertMetafield(Store $store, Model $model, MetafieldsData $data): Metafield
    {
        // 🔥 دمج value مع reference ids (مؤقت)
        $storedValue = [
            'value' => $data->value,
            'reference_id' => $data->referenceId,
            'reference_ids' => $data->referenceIds,
        ];

        $metafield = Metafield::updateOrCreate(
            [
                'store_id' => $store->id,
                'metafieldable_type' => $model->getMorphClass(),
                'metafieldable_id' => $model->id,
                'namespace' => $data->namespace,
                'key' => $data->key,
            ],
            [
                'shopify_metafield_id' => $data->shopifyMetafieldId,
                'value' => $storedValue, // 🔥 هون التعديل
                'type' => $data->type ?? 'string',
            ]
        );

        $this->syncMetaobjects($metafield, $data->metaobjects);

        return $metafield;
    }

    private function syncMetaobjects(Metafield $metafield, array $metaobjects): void
    {
        if (empty($metaobjects)) {
            $metafield->metaobjects()->detach();

            return;
        }

        $ids = [];

        foreach ($metaobjects as $obj) {
            if (empty($obj['id'])) {
                continue;
            }

            $metaobject = MetaObject::updateOrCreate(
                [
                    'shopify_metaobject_id' => $obj['id'],
                ],
                [
                    'store_id' => $metafield->store_id,
                    'type' => $obj['type'],
                    'fields' => $obj['fields'],
                ]
            );

            $ids[] = $metaobject->id;
        }

        $metafield->metaobjects()->sync($ids);
    }
}
