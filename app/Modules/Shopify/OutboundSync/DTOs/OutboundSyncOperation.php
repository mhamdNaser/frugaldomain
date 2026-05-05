<?php

namespace App\Modules\Shopify\OutboundSync\DTOs;

class OutboundSyncOperation
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed>|null $responsePayload
     */
    public function __construct(
        public readonly int $id,
        public readonly string $storeId,
        public readonly string $entityType,
        public readonly string $entityId,
        public readonly string $action,
        public readonly string $handler,
        public readonly string $status,
        public readonly int $attempts,
        public readonly int $maxAttempts,
        public readonly array $payload,
        public readonly ?array $responsePayload,
    ) {}

    public static function fromRow(object $row): self
    {
        return new self(
            id: (int) $row->id,
            storeId: (string) $row->store_id,
            entityType: (string) $row->entity_type,
            entityId: (string) $row->entity_id,
            action: (string) $row->action,
            handler: (string) $row->handler,
            status: (string) $row->status,
            attempts: (int) $row->attempts,
            maxAttempts: (int) $row->max_attempts,
            payload: self::decodeJsonToArray($row->payload ?? null),
            responsePayload: self::decodeJsonToArray($row->response_payload ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeJsonToArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}

