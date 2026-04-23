<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\CMS\Models\Metafield;
use App\Modules\CMS\Models\MetaObject;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Shopify\Support\ShopifyHelper;
use App\Modules\Stores\Models\Store;

class SyncMetaobjectsService
{
    public function handle(Store $store)
    {
        $client = new ShopifyClient($store);

        $ids = Metafield::where('store_id', $store->id)
            ->get()
            ->flatMap(function ($model) {

                $value = $model->value;

                if (is_string($value)) {
                    $value = json_decode($value, true);
                }

                if (!is_array($value)) {
                    return [];
                }

                $refs = [];
                if (isset($value['value']['reference_ids'])) {
                    $refs = $value['value']['reference_ids'];
                }

                elseif (isset($value['reference_ids'])) {
                    $refs = $value['reference_ids'];
                }

                return is_array($refs) ? $refs : [];
            })
            ->filter()  
            ->unique()
            ->values()
            ->toArray();

        if (empty($ids)) return;

        // 🔥 2. query
        $query = <<<GRAPHQL
        query getMetaobjects(\$ids: [ID!]!) {
          nodes(ids: \$ids) {
            ... on Metaobject {
              id
              type
              fields {
                key
                value
              }
            }
          }
        }
        GRAPHQL;

        $response = $client->query($query, [
            'ids' => array_map(fn($id) => "gid://shopify/Metaobject/$id", $ids)
        ]);

        $nodes = $response['data']['nodes'] ?? [];

        foreach ($nodes as $node) {
            if (empty($node['id'])) continue;

            $metaobject = MetaObject::updateOrCreate(
                [
                    'shopify_metaobject_id' => $node['id'],
                ],
                [
                    'store_id' => $store->id,
                    'type' => $node['type'] ?? null,
                    'fields' => $node['fields'] ?? [],
                ]
            );

            // 🔥 هون الربط المهم
            $this->attachMetaobjectToMetafields($metaobject, $node['id']);
        }
    }

    private function attachMetaobjectToMetafields(MetaObject $metaobject, string $gid): void
    {
        $id = ShopifyHelper::extractId($gid);

        if (!$id) return;

        $metafields = Metafield::where('store_id', $metaobject->store_id)
            ->get()
            ->filter(function ($metafield) use ($id) {

                $value = $metafield->value;

                if (is_string($value)) {
                    $value = json_decode($value, true);
                }

                if (!is_array($value)) {
                    return false;
                }

                $refs = $value['value']['reference_ids']
                    ?? $value['reference_ids']
                    ?? [];

                return in_array($id, $refs);
            });

        foreach ($metafields as $metafield) {
            $metafield->metaobjects()->syncWithoutDetaching([
                $metaobject->id
            ]);
        }
    }
}
