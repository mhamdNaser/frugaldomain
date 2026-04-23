<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\CMS\Models\MetaobjectDefinition;
use App\Modules\CMS\Models\MetaobjectDefinitionField;
use App\Modules\Shopify\DTOs\MetaobjectDefinitionData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;

class MetaobjectDefinitionsSyncService
{
    private const PAGE_SIZE = 100;

    public function sync(Store $store): int
    {
        $client = new ShopifyClient($store);
        $count = 0;
        $after = null;

        do {
            $response = $client->query(
                query: $this->query(),
                variables: array_filter([
                    'first' => self::PAGE_SIZE,
                    'after' => $after,
                ])
            );

            $connection = $response['data']['metaobjectDefinitions'] ?? null;
            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $edge) {
                $node = $edge['node'] ?? null;
                if (!is_array($node) || empty($node['id'])) {
                    continue;
                }

                $data = $this->map($node);
                $this->persist($store, $data);
                $count++;
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $count;
    }

    private function persist(Store $store, MetaobjectDefinitionData $data): void
    {
        $definition = MetaobjectDefinition::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_metaobject_definition_id' => $data->shopifyMetaobjectDefinitionId,
            ],
            [
                'type' => $data->type,
                'name' => $data->name,
                'display_name_key' => $data->displayNameKey,
                'access' => $data->access,
                'capabilities' => $data->capabilities,
                'raw_payload' => $data->rawPayload,
            ]
        );

        foreach ($data->fields as $field) {
            MetaobjectDefinitionField::query()->updateOrCreate(
                [
                    'metaobject_definition_id' => $definition->id,
                    'field_key' => $field['key'] ?? '',
                ],
                [
                    'name' => $field['name'] ?? null,
                    'type' => $field['type']['name'] ?? ($field['type'] ?? null),
                    'required' => (bool) ($field['required'] ?? false),
                    'validations' => $field['validations'] ?? [],
                    'raw_payload' => $field,
                ]
            );
        }
    }

    private function map(array $node): MetaobjectDefinitionData
    {
        return new MetaobjectDefinitionData(
            shopifyMetaobjectDefinitionId: (string) $node['id'],
            type: $node['type'] ?? null,
            name: $node['name'] ?? null,
            displayNameKey: $node['displayNameKey'] ?? null,
            access: $node['access'] ?? [],
            capabilities: $node['capabilities'] ?? [],
            fields: $node['fieldDefinitions'] ?? [],
            rawPayload: $node,
        );
    }

    private function query(): string
    {
        return <<<'GRAPHQL'
query SyncMetaobjectDefinitions($first: Int!, $after: String) {
  metaobjectDefinitions(first: $first, after: $after) {
    edges {
      node {
        id
        type
        name
        displayNameKey
        access {
          admin
          storefront
        }
        capabilities {
          publishable {
            enabled
          }
          translatable {
            enabled
          }
        }
        fieldDefinitions {
          key
          name
          required
          type {
            name
          }
          validations {
            name
            value
          }
        }
      }
    }
    pageInfo {
      hasNextPage
      endCursor
    }
  }
}
GRAPHQL;
    }
}

