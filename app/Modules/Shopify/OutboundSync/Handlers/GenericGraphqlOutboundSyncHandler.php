<?php

namespace App\Modules\Shopify\OutboundSync\Handlers;

use App\Modules\Shopify\OutboundSync\Contracts\OutboundSyncHandlerInterface;
use App\Modules\Shopify\OutboundSync\DTOs\OutboundSyncOperation;
use App\Modules\Shopify\OutboundSync\DTOs\OutboundSyncResult;
use App\Modules\Shopify\OutboundSync\Support\ArrayPath;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use InvalidArgumentException;

class GenericGraphqlOutboundSyncHandler implements OutboundSyncHandlerInterface
{
    public function handle(Store $store, OutboundSyncOperation $operation): OutboundSyncResult
    {
        $payload = $operation->payload;
        $mutation = $this->resolveMutation($payload);
        $variables = is_array($payload['variables'] ?? null) ? $payload['variables'] : [];

        $response = (new ShopifyClient($store))->query($mutation, $variables);
        $userErrors = $this->resolveUserErrors($response, $payload);

        if ($userErrors !== []) {
            return OutboundSyncResult::failure(
                errorCode: 'shopify_user_errors',
                errorMessage: implode(' | ', $userErrors),
                retryable: false,
                httpStatus: 200,
                responsePayload: $response,
            );
        }

        $resourcePath = is_string($payload['resource_path'] ?? null) ? $payload['resource_path'] : null;
        $resourceId = ArrayPath::get($response, $resourcePath);

        return OutboundSyncResult::success(
            responsePayload: $response,
            shopifyResourceId: is_scalar($resourceId) ? (string) $resourceId : null,
            httpStatus: 200,
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveMutation(array $payload): string
    {
        $mutation = $payload['mutation'] ?? $payload['query'] ?? null;

        if (!is_string($mutation) || trim($mutation) === '') {
            throw new InvalidArgumentException('Outbound payload must include a GraphQL mutation/query string.');
        }

        return $mutation;
    }

    /**
     * @param array<string, mixed> $response
     * @param array<string, mixed> $payload
     * @return array<int, string>
     */
    private function resolveUserErrors(array $response, array $payload): array
    {
        $messages = [];
        $configuredPath = is_string($payload['user_errors_path'] ?? null) ? $payload['user_errors_path'] : null;

        if ($configuredPath) {
            $errors = ArrayPath::get($response, $configuredPath, []);
            if (is_array($errors)) {
                return $this->normalizeUserErrors($errors);
            }
        }

        $this->collectNestedUserErrors($response, $messages);

        return array_values(array_unique(array_filter($messages)));
    }

    /**
     * @param array<int, mixed> $errors
     * @return array<int, string>
     */
    private function normalizeUserErrors(array $errors): array
    {
        $messages = [];

        foreach ($errors as $error) {
            if (is_array($error) && isset($error['message']) && is_string($error['message'])) {
                $messages[] = $error['message'];
            } elseif (is_string($error)) {
                $messages[] = $error;
            }
        }

        return array_values(array_unique(array_filter($messages)));
    }

    /**
     * @param array<string, mixed> $node
     * @param array<int, string> $messages
     */
    private function collectNestedUserErrors(array $node, array &$messages): void
    {
        foreach ($node as $key => $value) {
            if ($key === 'userErrors' && is_array($value)) {
                $messages = array_merge($messages, $this->normalizeUserErrors($value));
                continue;
            }

            if (is_array($value)) {
                $this->collectNestedUserErrors($value, $messages);
            }
        }
    }
}

