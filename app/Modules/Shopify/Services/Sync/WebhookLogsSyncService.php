<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WebhookLogsSyncService
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

            $connection = $response['data']['events'] ?? null;

            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $edge) {
                $node = $edge['node'] ?? null;

                if (!is_array($node) || empty($node['id'])) {
                    continue;
                }

                DB::table('webhook_logs')->updateOrInsert(
                    [
                        'store_id' => $store->id,
                        'provider' => 'shopify',
                        'external_id' => (string) $node['id'],
                    ],
                    [
                        'topic' => $this->topic($node),
                        'payload' => json_encode($node),
                        'status' => 'processed',
                        'attempts' => 1,
                        'error_message' => null,
                        'received_at' => $this->receivedAt($node['createdAt'] ?? null),
                        'processed_at' => now(),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $count++;
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $count;
    }

    private function topic(array $event): string
    {
        $subjectType = strtolower((string) ($event['subjectType'] ?? 'unknown'));
        $verb = strtolower((string) ($event['action'] ?? 'update'));

        return $subjectType . '/' . $verb;
    }

    private function receivedAt(?string $createdAt): string
    {
        if (!$createdAt) {
            return now()->toDateTimeString();
        }

        try {
            return Carbon::parse($createdAt)->toDateTimeString();
        } catch (\Throwable) {
            return now()->toDateTimeString();
        }
    }

    private function query(): string
    {
        return <<<'GRAPHQL'
query SyncEvents($first: Int!, $after: String) {
  events(first: $first, after: $after) {
    edges {
      node {
        __typename
        id
        message
        createdAt
        ... on BasicEvent {
          action
          subjectType
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
