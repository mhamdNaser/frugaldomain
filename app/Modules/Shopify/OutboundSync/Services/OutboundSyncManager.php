<?php

namespace App\Modules\Shopify\OutboundSync\Services;

use App\Modules\Shopify\OutboundSync\DTOs\EnqueueOutboundSyncData;
use App\Modules\Shopify\OutboundSync\Enums\OutboundSyncStatus;
use App\Modules\Shopify\OutboundSync\Jobs\ProcessOutboundSyncJob;
use Illuminate\Support\Facades\DB;

class OutboundSyncManager
{
    public function enqueue(EnqueueOutboundSyncData $data): int
    {
        $idempotencyKey = $data->idempotencyKey ?: $this->makeIdempotencyKey($data);
        $existing = DB::table('shopify_outbound_syncs')
            ->where('store_id', $data->storeId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            return (int) $existing->id;
        }

        return (int) DB::table('shopify_outbound_syncs')->insertGetId([
            'store_id' => $data->storeId,
            'entity_type' => $data->entityType,
            'entity_id' => $data->entityId,
            'action' => $data->action,
            'handler' => $data->handler,
            'status' => OutboundSyncStatus::PENDING,
            'priority' => max(0, min(9, $data->priority)),
            'attempts' => 0,
            'max_attempts' => max(1, $data->maxAttempts),
            'available_at' => now(),
            'locked_at' => null,
            'last_attempt_at' => null,
            'next_retry_at' => null,
            'synced_at' => null,
            'correlation_id' => $data->correlationId ?: (string) str()->uuid(),
            'idempotency_key' => $idempotencyKey,
            'shopify_resource_id' => null,
            'payload' => $this->encodeJson($data->payload),
            'response_payload' => null,
            'last_error_code' => null,
            'last_error_message' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function enqueueAndDispatch(EnqueueOutboundSyncData $data): int
    {
        $id = $this->enqueue($data);
        $this->dispatch($id);

        return $id;
    }

    public function dispatch(int $id): void
    {
        ProcessOutboundSyncJob::dispatch($id);
    }

    public function retry(int $id, string $storeId): bool
    {
        $updated = DB::table('shopify_outbound_syncs')
            ->where('id', $id)
            ->where('store_id', $storeId)
            ->whereIn('status', [OutboundSyncStatus::FAILED, OutboundSyncStatus::DEAD])
            ->update([
                'status' => OutboundSyncStatus::PENDING,
                'available_at' => now(),
                'next_retry_at' => null,
                'locked_at' => null,
                'last_error_code' => null,
                'last_error_message' => null,
                'updated_at' => now(),
            ]);

        if ($updated > 0) {
            $this->dispatch($id);
            return true;
        }

        return false;
    }

    private function makeIdempotencyKey(EnqueueOutboundSyncData $data): string
    {
        return hash(
            'sha256',
            implode('|', [
                $data->storeId,
                $data->entityType,
                $data->entityId,
                $data->action,
                $data->handler,
                $this->encodeJson($data->payload),
            ])
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function encodeJson(array $data): string
    {
        return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

