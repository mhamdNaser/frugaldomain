<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\CMS\Models\Theme;
use App\Modules\CMS\Models\ThemeAsset;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use Illuminate\Support\Facades\DB;

class ThemesSyncService
{
    private const PAGE_SIZE = 50;
    private const ASSET_PAGE_SIZE = 250;

    /**
     * @return array<string, int>
     */
    public function sync(Store $store): array
    {
        $client = new ShopifyClient($store);
        $themeCount = 0;
        $assetCount = 0;
        $after = null;

        do {
            $response = $client->query(
                query: $this->themesQuery(),
                variables: array_filter([
                    'first' => self::PAGE_SIZE,
                    'after' => $after,
                ]),
            );

            $connection = $response['data']['themes'] ?? null;
            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $edge) {
                $node = $edge['node'] ?? null;
                if (!is_array($node) || empty($node['id'])) {
                    continue;
                }

                $theme = $this->upsertTheme($store, $node);
                $themeCount++;

                // Asset fetch may be unavailable for some API versions/scopes. Skip safely.
                $assetCount += $this->syncAssetsForTheme($store, $theme, $client);
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return [
            'themes' => $themeCount,
            'theme_assets' => $assetCount,
        ];
    }

    private function upsertTheme(Store $store, array $node): Theme
    {
        return Theme::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_theme_id' => (string) $node['id'],
            ],
            [
                'name' => $node['name'] ?? null,
                'role' => isset($node['role']) ? strtolower((string) $node['role']) : null,
                'processing' => (bool) ($node['processing'] ?? false),
                'previewable' => (bool) ($node['previewable'] ?? false),
                'raw_payload' => $node,
                'shopify_created_at' => $node['createdAt'] ?? null,
                'shopify_updated_at' => $node['updatedAt'] ?? null,
            ],
        );
    }

    private function syncAssetsForTheme(Store $store, Theme $theme, ShopifyClient $client): int
    {
        try {
            $response = $client->query(
                query: $this->themeAssetsQuery(),
                variables: [
                    'id' => $theme->shopify_theme_id,
                    'first' => self::ASSET_PAGE_SIZE,
                ],
            );
        } catch (\Throwable) {
            return 0;
        }

        $connection = $response['data']['theme']['files'] ?? null;
        if (!is_array($connection)) {
            return 0;
        }

        $saved = 0;
        DB::transaction(function () use ($store, $theme, $connection, &$saved) {
            foreach ($connection['edges'] ?? [] as $edge) {
                $node = $edge['node'] ?? null;
                if (!is_array($node) || empty($node['filename'])) {
                    continue;
                }

                ThemeAsset::query()->updateOrCreate(
                    [
                        'theme_id' => $theme->id,
                        'filename' => (string) $node['filename'],
                    ],
                    [
                        'store_id' => $store->id,
                        'shopify_asset_id' => isset($node['id']) ? (string) $node['id'] : null,
                        'content_type' => $node['contentType'] ?? null,
                        'size' => isset($node['size']) ? (int) $node['size'] : null,
                        'url' => $node['url'] ?? null,
                        'raw_payload' => $node,
                        'shopify_created_at' => $node['createdAt'] ?? null,
                        'shopify_updated_at' => $node['updatedAt'] ?? null,
                    ],
                );

                $saved++;
            }
        });

        return $saved;
    }

    private function themesQuery(): string
    {
        return <<<'GRAPHQL'
query GetThemes($first: Int!, $after: String) {
  themes(first: $first, after: $after) {
    edges {
      node {
        id
        name
        role
        previewable
        processing
        createdAt
        updatedAt
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

    private function themeAssetsQuery(): string
    {
        return <<<'GRAPHQL'
query GetThemeAssets($id: ID!, $first: Int!) {
  theme(id: $id) {
    id
    files(first: $first) {
      edges {
        node {
          id
          filename
          contentType
          size
          url
          createdAt
          updatedAt
        }
      }
    }
  }
}
GRAPHQL;
    }
}

