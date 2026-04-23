<?php

namespace App\Modules\Shopify\Webhooks\Services;

use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShopifyWebhookTopicRegistrar
{
    public function __construct(
        private readonly ShopifyWebhookTopicCatalog $catalog,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function registerForStore(Store $store): array
    {
        $client = new ShopifyClient($store);
        $callbackUrl = $this->callbackUrl();
        $topics = $this->catalog->all();

        $existingByTopic = $this->existingTopicsByCallback($client, $callbackUrl);

        $created = [];
        $alreadyExists = [];
        $failed = [];

        foreach ($topics as $topic) {
            if (isset($existingByTopic[$topic])) {
                $alreadyExists[] = $topic;
                continue;
            }

            try {
                $response = $client->query(
                    query: $this->createMutation(),
                    variables: [
                        'topic' => $topic,
                        'callbackUrl' => $callbackUrl,
                    ]
                );

                $payload = $response['data']['webhookSubscriptionCreate'] ?? null;
                $errors = $payload['userErrors'] ?? [];

                if (is_array($errors) && $errors !== []) {
                    $failed[] = [
                        'topic' => $topic,
                        'errors' => $errors,
                    ];
                    continue;
                }

                $created[] = $topic;
            } catch (\Throwable $e) {
                $failed[] = [
                    'topic' => $topic,
                    'errors' => [['message' => $e->getMessage()]],
                ];
            }
        }

        return [
            'store_id' => $store->id,
            'shopify_domain' => $store->shopify_domain,
            'callback_url' => $callbackUrl,
            'requested_count' => count($topics),
            'created_count' => count($created),
            'already_exists_count' => count($alreadyExists),
            'failed_count' => count($failed),
            'created_topics' => $created,
            'already_exists_topics' => $alreadyExists,
            'failed_topics' => $failed,
        ];
    }

    /**
     * Multi-tenant entrypoint: register topics only for the authenticated user's store.
     */
    public function registerForAuthenticatedUser(?Authenticatable $user): array
    {
        if (!$user || !isset($user->id)) {
            throw new ModelNotFoundException('Authenticated user is required to resolve tenant store.');
        }

        return $this->registerForOwnerId((string) $user->id);
    }

    /**
     * Multi-tenant entrypoint: register topics only for store owned by given user id.
     */
    public function registerForOwnerId(string $ownerId): array
    {
        $store = Store::query()
            ->where('owner_id', $ownerId)
            ->whereNotNull('shopify_domain')
            ->whereNotNull('shopify_access_token')
            ->first();

        if (!$store) {
            throw new ModelNotFoundException('No Shopify-connected store found for this owner.');
        }

        return $this->registerForStore($store);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function registerForAllStores(): array
    {
        $results = [];

        Store::query()
            ->whereNotNull('shopify_domain')
            ->whereNotNull('shopify_access_token')
            ->orderBy('created_at')
            ->chunkById(50, function ($stores) use (&$results) {
                foreach ($stores as $store) {
                    try {
                        $results[] = $this->registerForStore($store);
                    } catch (\Throwable $e) {
                        $results[] = [
                            'store_id' => $store->id,
                            'shopify_domain' => $store->shopify_domain,
                            'requested_count' => count($this->catalog->all()),
                            'created_count' => 0,
                            'already_exists_count' => 0,
                            'failed_count' => 1,
                            'created_topics' => [],
                            'already_exists_topics' => [],
                            'failed_topics' => [[
                                'topic' => '*',
                                'errors' => [['message' => $e->getMessage()]],
                            ]],
                        ];
                    }
                }
            }, 'id');

        return $results;
    }

    /**
     * @return array<string, string>
     */
    private function existingTopicsByCallback(ShopifyClient $client, string $callbackUrl): array
    {
        $topics = [];
        $after = null;

        do {
            $response = $client->query(
                query: $this->listQuery(),
                variables: array_filter([
                    'first' => 100,
                    'after' => $after,
                ])
            );

            $connection = $response['data']['webhookSubscriptions'] ?? null;
            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $edge) {
                $node = $edge['node'] ?? [];
                $topic = (string) ($node['topic'] ?? '');
                $registeredCallback = (string) ($node['endpoint']['callbackUrl'] ?? '');

                if ($topic !== '' && $this->sameCallback($registeredCallback, $callbackUrl)) {
                    $topics[$topic] = $topic;
                }
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $topics;
    }

    private function callbackUrl(): string
    {
        $fromEnv = trim((string) env('SHOPIFY_WEBHOOK_ENDPOINT', ''));
        if ($fromEnv !== '') {
            return rtrim($fromEnv, '/');
        }

        $appUrl = trim((string) config('app.url', ''));
        if ($appUrl !== '') {
            return rtrim($appUrl, '/') . '/api/shopify/webhooks';
        }

        return '/api/shopify/webhooks';
    }

    /**
     * Notes:
     * - Access token is stored encrypted in DB.
     * - Decryption is handled centrally by ShopifyClient via Crypt::decryptString(...)
     *   before each GraphQL call, so this registrar remains tenant-safe and token-safe.
     */

    private function sameCallback(string $left, string $right): bool
    {
        return rtrim(mb_strtolower(trim($left)), '/') === rtrim(mb_strtolower(trim($right)), '/');
    }

    private function listQuery(): string
    {
        return <<<'GRAPHQL'
query ExistingWebhookSubscriptions($first: Int!, $after: String) {
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

    private function createMutation(): string
    {
        return <<<'GRAPHQL'
mutation CreateWebhookSubscription($topic: WebhookSubscriptionTopic!, $callbackUrl: URL!) {
  webhookSubscriptionCreate(
    topic: $topic
    webhookSubscription: {
      callbackUrl: $callbackUrl
      format: JSON
    }
  ) {
    webhookSubscription {
      id
      topic
    }
    userErrors {
      field
      message
    }
  }
}
GRAPHQL;
    }
}
