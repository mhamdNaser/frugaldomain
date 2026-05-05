<?php

namespace App\Modules\Shopify\OutboundSync\Services;

use App\Modules\Shopify\OutboundSync\Actions\QueueOutboundSyncAction;
use App\Modules\Shopify\OutboundSync\DTOs\EnqueueOutboundSyncData;
use App\Modules\Shopify\OutboundSync\Handlers\GenericGraphqlOutboundSyncHandler;

class LocalChangeOutboundSyncDispatcher
{
    public function __construct(
        private readonly QueueOutboundSyncAction $queueAction,
    ) {}

    /**
     * @param array<string, mixed> $validated
     */
    public function dispatchFromValidated(
        array $validated,
        string $storeId,
        string $entityType,
        string $entityId,
        string $action = 'update',
    ): ?int {
        $shopifySync = $validated['shopify_sync'] ?? null;

        if (!is_array($shopifySync)) {
            return null;
        }

        $payload = is_array($shopifySync['payload'] ?? null) ? $shopifySync['payload'] : [];

        if (!isset($payload['mutation']) && is_string($shopifySync['mutation'] ?? null)) {
            $payload['mutation'] = $shopifySync['mutation'];
        }

        if (!isset($payload['query']) && is_string($shopifySync['query'] ?? null)) {
            $payload['query'] = $shopifySync['query'];
        }

        if (!isset($payload['variables']) && is_array($shopifySync['variables'] ?? null)) {
            $payload['variables'] = $shopifySync['variables'];
        }

        if (!isset($payload['resource_path']) && is_string($shopifySync['resource_path'] ?? null)) {
            $payload['resource_path'] = $shopifySync['resource_path'];
        }

        if (!isset($payload['user_errors_path']) && is_string($shopifySync['user_errors_path'] ?? null)) {
            $payload['user_errors_path'] = $shopifySync['user_errors_path'];
        }

        if (!is_string($payload['mutation'] ?? null) && !is_string($payload['query'] ?? null)) {
            return null;
        }

        $id = $this->queueAction->execute(new EnqueueOutboundSyncData(
            storeId: $storeId,
            entityType: $entityType,
            entityId: $entityId,
            action: $action,
            handler: GenericGraphqlOutboundSyncHandler::class,
            payload: $payload,
            idempotencyKey: is_string($shopifySync['idempotency_key'] ?? null) ? $shopifySync['idempotency_key'] : null,
            correlationId: is_string($shopifySync['correlation_id'] ?? null) ? $shopifySync['correlation_id'] : null,
            priority: isset($shopifySync['priority']) ? max(0, min(9, (int) $shopifySync['priority'])) : 5,
            maxAttempts: isset($shopifySync['max_attempts']) ? max(1, min(20, (int) $shopifySync['max_attempts'])) : 5,
        ));

        return $id;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function dispatchGraphql(
        string $storeId,
        string $entityType,
        string $entityId,
        string $action,
        array $payload,
        ?string $idempotencyKey = null,
        ?string $correlationId = null,
        int $priority = 5,
        int $maxAttempts = 5,
    ): ?int {
        if (!is_string($payload['mutation'] ?? null) && !is_string($payload['query'] ?? null)) {
            return null;
        }

        return $this->queueAction->execute(new EnqueueOutboundSyncData(
            storeId: $storeId,
            entityType: $entityType,
            entityId: $entityId,
            action: $action,
            handler: GenericGraphqlOutboundSyncHandler::class,
            payload: $payload,
            idempotencyKey: $idempotencyKey,
            correlationId: $correlationId,
            priority: max(0, min(9, $priority)),
            maxAttempts: max(1, min(20, $maxAttempts)),
        ));
    }
}
