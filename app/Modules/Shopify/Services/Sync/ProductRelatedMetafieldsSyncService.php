<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Catalog\Models\Collection;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Shopify\Support\ShopifyHelper;
use App\Modules\Stores\Models\Store;
use Illuminate\Database\Eloquent\Model;

class ProductRelatedMetafieldsSyncService
{
    private const METAFIELDS_PAGE_SIZE = 25;
    private const REFERENCES_PAGE_SIZE = 10;

    public function __construct(
        private readonly MetafieldSyncService $metafieldSync,
        private readonly CollectionSyncService $collectionSync,
        private readonly VariantSyncService $variantSync,
    ) {}

    public function syncByProduct(Store $store, Product $product, string $shopifyProductId): void
    {
        $this->collectionSync->syncByProduct($store, $product, $shopifyProductId);
        $this->variantSync->syncByProduct($store, $product, $shopifyProductId);

        $product->load(['collections', 'variants']);

        $this->syncModelMetafields($store, $product, $shopifyProductId);

        $product->collections()
            ->whereNotNull('shopify_collection_id')
            ->each(fn (Collection $collection) => $this->syncModelMetafields(
                store: $store,
                model: $collection,
                ownerId: $collection->shopify_collection_id,
            ));

        $product->variants()
            ->whereNotNull('shopify_variant_id')
            ->each(fn (ProductVariant $variant) => $this->syncModelMetafields(
                store: $store,
                model: $variant,
                ownerId: $variant->shopify_variant_id,
            ));
    }

    private function fetchMetafields(Store $store, string $ownerId): array
    {
        $client = new ShopifyClient($store);
        $edges = [];
        $after = null;

        do {
            $response = $client->query(
                query: $this->query(),
                variables: array_filter([
                    'id' => $ownerId,
                    'first' => self::METAFIELDS_PAGE_SIZE,
                    'after' => $after,
                    'referencesFirst' => self::REFERENCES_PAGE_SIZE,
                ]),
            );

            $connection = $response['data']['node']['metafields'] ?? null;

            if (!is_array($connection)) {
                break;
            }

            $edges = array_merge($edges, $connection['edges'] ?? []);
            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $edges;
    }

    private function syncModelMetafields(Store $store, Model $model, string $ownerId): void
    {
        $metafields = [];

        foreach ($this->fetchMetafields($store, $ownerId) as $edge) {
            $node = $edge['node'] ?? null;

            if (!is_array($node) || empty($node['namespace']) || empty($node['key'])) {
                continue;
            }

            $metafields[] = $this->mapMetafield($node);
        }

        $this->metafieldSync->sync($store, $model, $metafields);
    }

    private function mapMetafield(array $node): array
    {
        $references = $this->extractReferenceIds($node);

        return [
            'shopifyMetafieldId' => ShopifyHelper::extractId($node['id'] ?? null),
            'namespace' => $node['namespace'] ?? null,
            'key' => $node['key'] ?? null,
            'type' => $node['type'] ?? null,
            'value' => ShopifyHelper::parseValue($node['value'] ?? null, $node['type'] ?? null),
            'referenceId' => $references[0] ?? null,
            'referenceIds' => $references,
            'metaobjects' => $this->extractMetaobjects($node),
        ];
    }

    private function extractReferenceIds(array $node): array
    {
        $value = $node['value'] ?? null;
        $type = $node['type'] ?? null;
        if (!$value || !$type || !str_contains($type, 'reference')) {
            return [];
        }

        $references = $this->referenceIdsFromGraphql($node);
        $valueReferences = str_starts_with($type, 'list.')
            ? json_decode((string) $value, true)
            : [$value];

        if (is_array($valueReferences)) {
            $references = array_merge(
                $references,
                array_map(
                    static fn ($reference) => is_string($reference) ? ShopifyHelper::extractId($reference) : null,
                    $valueReferences
                )
            );
        }

        return array_values(array_filter(array_unique($references)));
    }

    private function referenceIdsFromGraphql(array $node): array
    {
        $ids = [];

        if (!empty($node['reference']['id'])) {
            $ids[] = ShopifyHelper::extractId($node['reference']['id']);
        }

        foreach ($node['references']['edges'] ?? [] as $edge) {
            $referenceId = $edge['node']['id'] ?? null;

            if ($referenceId) {
                $ids[] = ShopifyHelper::extractId($referenceId);
            }
        }

        return array_values(array_filter($ids));
    }

    private function extractMetaobjects(array $node): array
    {
        $metaobjects = [];

        if (($node['reference']['__typename'] ?? null) === 'Metaobject') {
            $metaobjects[] = $this->mapMetaobject($node['reference']);
        }

        foreach ($node['references']['edges'] ?? [] as $edge) {
            $reference = $edge['node'] ?? null;

            if (($reference['__typename'] ?? null) === 'Metaobject') {
                $metaobjects[] = $this->mapMetaobject($reference);
            }
        }

        return array_values(array_filter($metaobjects));
    }

    private function mapMetaobject(array $node): ?array
    {
        if (empty($node['id'])) {
            return null;
        }

        return [
            'id' => $node['id'],
            'type' => $node['type'] ?? 'unknown',
            'fields' => $node['fields'] ?? [],
        ];
    }

    private function query(): string
    {
        return <<<'GRAPHQL'
query GetOwnerMetafields($id: ID!, $first: Int!, $after: String, $referencesFirst: Int!) {
  node(id: $id) {
    ... on Product {
      metafields(first: $first, after: $after) {
        ...MetafieldsConnectionFields
      }
    }
    ... on Collection {
      metafields(first: $first, after: $after) {
        ...MetafieldsConnectionFields
      }
    }
    ... on ProductVariant {
      metafields(first: $first, after: $after) {
        ...MetafieldsConnectionFields
      }
    }
  }
}

fragment MetafieldsConnectionFields on MetafieldConnection {
  edges {
    node {
      id
      namespace
      key
      type
      value
      reference {
        ...MetafieldReferenceFields
      }
      references(first: $referencesFirst) {
        edges {
          node {
            ...MetafieldReferenceFields
          }
        }
      }
    }
  }
  pageInfo {
    hasNextPage
    endCursor
  }
}

fragment MetafieldReferenceFields on MetafieldReference {
  __typename
  ... on Product {
    id
  }
  ... on ProductVariant {
    id
  }
  ... on Collection {
    id
  }
  ... on Metaobject {
    id
    type
    fields {
      key
      value
    }
  }
  ... on MediaImage {
    id
  }
  ... on GenericFile {
    id
  }
  ... on Video {
    id
  }
  ... on Model3d {
    id
  }
  ... on Page {
    id
  }
}
GRAPHQL;
    }
}
