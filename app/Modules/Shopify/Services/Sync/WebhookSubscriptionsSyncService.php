<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Shopify\DTOs\WebhookSubscriptionData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WebhookSubscriptionsSyncService
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

            $connection = $response['data']['webhookSubscriptions'] ?? null;

            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $edge) {
                $node = $edge['node'] ?? null;

                if (!is_array($node) || empty($node['id']) || empty($node['topic'])) {
                    continue;
                }

                $data = new WebhookSubscriptionData(
                    shopifyWebhookId: (string) $node['id'],
                    topic: (string) $node['topic'],
                    event: strtolower(str_replace('_', '/', (string) $node['topic'])),
                    callbackUrl: $this->endpointUri($node['endpoint'] ?? []),
                    isActive: true,
                    provider: 'shopify',
                    endpointType: $node['endpoint']['__typename'] ?? null,
                    format: null,
                    rawPayload: $node,
                );

                $this->upsert($store->id, $data);
                $count++;
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $count;
    }

    private function upsert(string $storeId, WebhookSubscriptionData $data): void
    {
        $payload = [
            'store_id' => $storeId,
            'event' => $data->event,
            'topic' => $data->topic,
            'callback_url' => $data->callbackUrl ?? '',
            'is_active' => $data->isActive,
            'provider' => $data->provider,
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('webhook_subscriptions', 'shopify_webhook_id')) {
            $payload['shopify_webhook_id'] = $data->shopifyWebhookId;
        }

        if (Schema::hasColumn('webhook_subscriptions', 'endpoint_type')) {
            $payload['endpoint_type'] = $data->endpointType;
        }

        if (Schema::hasColumn('webhook_subscriptions', 'format')) {
            $payload['format'] = $data->format;
        }

        if (Schema::hasColumn('webhook_subscriptions', 'raw_payload')) {
            $payload['raw_payload'] = json_encode($data->rawPayload);
        }

        DB::table('webhook_subscriptions')->updateOrInsert(
            [
                'store_id' => $storeId,
                'event' => $data->event,
                'provider' => $data->provider,
            ],
            array_merge($payload, ['created_at' => now()])
        );
    }

    private function endpointUri(array $endpoint): ?string
    {
        return $endpoint['callbackUrl']
            ?? $endpoint['uri']
            ?? $endpoint['arn']
            ?? (isset($endpoint['pubSubProject'], $endpoint['pubSubTopic'])
                ? $endpoint['pubSubProject'] . '/' . $endpoint['pubSubTopic']
                : null);
    }

    private function query(): string
    {
        return <<<'GRAPHQL'
query SyncWebhookSubscriptions($first: Int!, $after: String) {
  webhookSubscriptions(first: $first, after: $after) {
    edges {
      node {
        id
        topic
        endpoint {
          __typename
          ... on WebhookHttpEndpoint {
            callbackUrl
          }
          ... on WebhookEventBridgeEndpoint {
            arn
          }
          ... on WebhookPubSubEndpoint {
            pubSubProject
            pubSubTopic
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
