<?php

namespace App\Modules\Shopify\AutoSync;

use App\Modules\Shopify\AutoSync\Exceptions\ShopifyUserErrorsException;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Thin wrapper over ShopifyClient used by the auto-sync syncers.
 */
class ShopifyGateway
{
    private ShopifyClient $client;

    private ?string $onlineStorePublicationId = null;

    private ?string $primaryLocationId = null;

    public function __construct(public readonly Store $store)
    {
        $this->client = new ShopifyClient($store);
    }

    /**
     * @param array<string, mixed> $variables
     * @return array<string, mixed>
     */
    public function query(string $graphql, array $variables = []): array
    {
        return $this->client->query($graphql, $variables)['data'] ?? [];
    }

    /**
     * Runs a mutation and returns data.<rootField>. Throws when Shopify returns userErrors.
     *
     * @param array<string, mixed> $variables
     * @return array<string, mixed>
     */
    public function mutate(string $graphql, array $variables, string $rootField): array
    {
        $payload = $this->query($graphql, $variables)[$rootField] ?? [];
        $payload = is_array($payload) ? $payload : [];

        $errors = array_merge(
            is_array($payload['userErrors'] ?? null) ? $payload['userErrors'] : [],
            is_array($payload['mediaUserErrors'] ?? null) ? $payload['mediaUserErrors'] : [],
        );

        if ($errors !== []) {
            $messages = collect($errors)
                ->map(function ($error) {
                    $field = is_array($error['field'] ?? null) ? implode('.', $error['field']) : null;
                    $message = (string) ($error['message'] ?? 'Shopify error');

                    return $field ? "{$field}: {$message}" : $message;
                })
                ->unique()
                ->implode(' | ');

            throw new ShopifyUserErrorsException("Shopify ({$rootField}): {$messages}", $errors);
        }

        return $payload;
    }

    /**
     * Publishes a product/collection to the Online Store channel. Failures are logged, never fatal.
     */
    public function publish(string $gid): void
    {
        $this->togglePublication($gid, true);
    }

    public function unpublish(string $gid): void
    {
        $this->togglePublication($gid, false);
    }

    public function primaryLocationId(): ?string
    {
        if ($this->primaryLocationId !== null) {
            return $this->primaryLocationId;
        }

        $data = $this->query('query { locations(first: 1, query: "active:true") { nodes { id } } }');

        return $this->primaryLocationId = $data['locations']['nodes'][0]['id'] ?? null;
    }

    /**
     * Sets the absolute "available" quantity of an inventory item at a location,
     * activating the item at that location first when needed.
     */
    public function setAvailableQuantity(string $inventoryItemId, string $locationId, int $quantity): void
    {
        $mutation = <<<'GQL'
mutation SetAvailable($input: InventorySetQuantitiesInput!, $idempotencyKey: String!) {
  inventorySetQuantities(input: $input) @idempotent(key: $idempotencyKey) {
    userErrors { field message code }
  }
}
GQL;
        $variables = [
            'input' => [
                'name' => 'available',
                'reason' => 'correction',
                'ignoreCompareQuantity' => true,
                'quantities' => [[
                    'inventoryItemId' => $inventoryItemId,
                    'locationId' => $locationId,
                    'quantity' => $quantity,
                ]],
            ],
            'idempotencyKey' => (string) Str::uuid(),
        ];

        try {
            $this->mutate($mutation, $variables, 'inventorySetQuantities');
        } catch (ShopifyUserErrorsException $e) {
            if (!str_contains(strtolower($e->getMessage()), 'stocked')) {
                throw $e;
            }

            $this->mutate(<<<'GQL'
mutation Activate($inventoryItemId: ID!, $locationId: ID!, $available: Int, $idempotencyKey: String!) {
  inventoryActivate(inventoryItemId: $inventoryItemId, locationId: $locationId, available: $available) @idempotent(key: $idempotencyKey) {
    inventoryLevel { id }
    userErrors { field message }
  }
}
GQL, [
                'inventoryItemId' => $inventoryItemId,
                'locationId' => $locationId,
                'available' => $quantity,
                'idempotencyKey' => (string) Str::uuid(),
            ], 'inventoryActivate');
        }
    }

    private function togglePublication(string $gid, bool $publish): void
    {
        try {
            $publicationId = $this->onlineStorePublicationId();

            if (!$publicationId) {
                return;
            }

            $field = $publish ? 'publishablePublish' : 'publishableUnpublish';

            $this->mutate(<<<GQL
mutation Toggle(\$id: ID!, \$input: [PublicationInput!]!) {
  {$field}(id: \$id, input: \$input) {
    userErrors { field message }
  }
}
GQL, [
                'id' => $gid,
                'input' => [['publicationId' => $publicationId]],
            ], $field);
        } catch (Throwable $e) {
            Log::warning('Shopify auto-sync: publication change failed', [
                'store_id' => $this->store->id,
                'gid' => $gid,
                'publish' => $publish,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function onlineStorePublicationId(): ?string
    {
        if ($this->onlineStorePublicationId !== null) {
            return $this->onlineStorePublicationId;
        }

        $nodes = $this->query('query { publications(first: 50) { nodes { id catalog { title } } } }')['publications']['nodes'] ?? [];

        foreach ($nodes as $node) {
            if (strcasecmp((string) ($node['catalog']['title'] ?? ''), 'Online Store') === 0) {
                return $this->onlineStorePublicationId = (string) $node['id'];
            }
        }

        return $this->onlineStorePublicationId = isset($nodes[0]['id']) ? (string) $nodes[0]['id'] : null;
    }
}
