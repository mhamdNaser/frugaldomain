<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\CMS\Models\File;
use App\Modules\Shopify\DTOs\GlobalFileData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Shopify\Support\ShopifyHelper;
use App\Modules\Stores\Models\Store;

class GlobalFilesSyncService
{
    private const PAGE_SIZE = 50;

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
                ]),
            );

            $connection = $response['data']['files'] ?? null;

            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $edge) {
                $node = $edge['node'] ?? null;

                $data = is_array($node) ? $this->fileData($node) : null;

                if ($data && $this->upsertFile($store, $data)) {
                    $count++;
                }
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $count;
    }

    private function upsertFile(Store $store, GlobalFileData $data): ?File
    {
        return File::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_id' => ShopifyHelper::extractId($data->shopifyFileId),
                'role' => 'global_file',
            ],
            [
                'disk' => 'shopify',
                'path' => $data->url,
                'url' => $data->url,
                'mime_type' => $data->mimeType,
                'width' => $data->width,
                'height' => $data->height,
                'altText' => $data->alt,
                'type' => $data->type,
                'position' => 0,
                'fileable_type' => null,
                'fileable_id' => null,
                'meta' => $data->rawPayload,
            ]
        );
    }

    private function fileData(array $node): ?GlobalFileData
    {
        $url = $this->url($node);

        if (!$url || empty($node['id'])) {
            return null;
        }

        return new GlobalFileData(
            shopifyFileId: $node['id'],
            url: $url,
            mimeType: $this->mimeType($node),
            width: $this->width($node),
            height: $this->height($node),
            alt: $node['alt'] ?? null,
            type: $this->type($node),
            rawPayload: $node,
        );
    }

    private function url(array $node): ?string
    {
        return $node['image']['url']
            ?? $node['url']
            ?? $node['sources'][0]['url']
            ?? $node['preview']['image']['url']
            ?? null;
    }

    private function mimeType(array $node): ?string
    {
        return $node['mimeType'] ?? $node['sources'][0]['mimeType'] ?? null;
    }

    private function width(array $node): ?int
    {
        return $node['image']['width'] ?? $node['preview']['image']['width'] ?? null;
    }

    private function height(array $node): ?int
    {
        return $node['image']['height'] ?? $node['preview']['image']['height'] ?? null;
    }

    private function type(array $node): string
    {
        return match ($node['__typename'] ?? null) {
            'MediaImage' => 'image',
            'Video' => 'video',
            'Model3d' => 'model',
            default => 'document',
        };
    }

    private function query(): string
    {
        return <<<'GRAPHQL'
query GetGlobalFiles($first: Int!, $after: String) {
  files(first: $first, after: $after) {
    edges {
      node {
        __typename
        ... on MediaImage {
          id
          alt
          image {
            url
            width
            height
          }
        }
        ... on GenericFile {
          id
          url
          mimeType
        }
        ... on Video {
          id
          alt
          sources {
            url
            mimeType
          }
          preview {
            image {
              url
              width
              height
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
}
GRAPHQL;
    }
}
