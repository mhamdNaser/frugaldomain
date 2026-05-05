<?php

namespace App\Modules\Shopify\OutboundSync\Services;

use App\Modules\Shopify\OutboundSync\Contracts\OutboundSyncHandlerInterface;
use App\Modules\Shopify\OutboundSync\DTOs\OutboundSyncOperation;
use App\Modules\Shopify\OutboundSync\DTOs\OutboundSyncResult;
use App\Modules\Shopify\OutboundSync\Enums\OutboundSyncStatus;
use App\Modules\Stores\Models\Store;
use Illuminate\Support\Facades\DB;
use Throwable;

class OutboundSyncProcessor
{
    public function process(int $outboundSyncId): void
    {
        $operation = $this->reserveForProcessing($outboundSyncId);

        if (!$operation) {
            return;
        }

        $attemptStartedAt = now();
        $startedAtMicro = microtime(true);
        $result = null;

        try {
            $store = Store::query()->find($operation->storeId);

            if (!$store) {
                $result = OutboundSyncResult::failure(
                    errorCode: 'store_not_found',
                    errorMessage: 'Store not found for outbound sync operation.',
                    retryable: false,
                );
            } else {
                $result = $this->resolveHandler($operation->handler)->handle($store, $operation);
            }
        } catch (Throwable $exception) {
            $result = OutboundSyncResult::fromThrowable(
                exception: $exception,
                retryable: $this->isRetryableException($exception),
            );
        }

        $durationMs = (int) round((microtime(true) - $startedAtMicro) * 1000);

        $this->storeAttempt(
            operation: $operation,
            result: $result,
            startedAt: $attemptStartedAt,
            durationMs: max(0, $durationMs),
        );

        if ($result->success) {
            $this->markSynced($operation->id, $result);
            return;
        }

        $this->markFailedOrRetrying($operation, $result);
    }

    private function reserveForProcessing(int $id): ?OutboundSyncOperation
    {
        return DB::transaction(function () use ($id) {
            $row = DB::table('shopify_outbound_syncs')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                return null;
            }

            if (in_array((string) $row->status, OutboundSyncStatus::TERMINAL, true)) {
                return null;
            }

            if ($row->status === OutboundSyncStatus::PROCESSING) {
                return null;
            }

            if ($row->available_at !== null && now()->lt($row->available_at)) {
                return null;
            }

            DB::table('shopify_outbound_syncs')
                ->where('id', $id)
                ->update([
                    'status' => OutboundSyncStatus::PROCESSING,
                    'attempts' => ((int) $row->attempts) + 1,
                    'locked_at' => now(),
                    'last_attempt_at' => now(),
                    'last_error_code' => null,
                    'last_error_message' => null,
                    'updated_at' => now(),
                ]);

            $updated = DB::table('shopify_outbound_syncs')
                ->where('id', $id)
                ->first();

            return $updated ? OutboundSyncOperation::fromRow($updated) : null;
        });
    }

    private function markSynced(int $id, OutboundSyncResult $result): void
    {
        DB::table('shopify_outbound_syncs')
            ->where('id', $id)
            ->update([
                'status' => OutboundSyncStatus::SYNCED,
                'synced_at' => now(),
                'next_retry_at' => null,
                'available_at' => null,
                'locked_at' => null,
                'shopify_resource_id' => $result->shopifyResourceId,
                'response_payload' => $this->encodeJson($result->responsePayload),
                'last_error_code' => null,
                'last_error_message' => null,
                'updated_at' => now(),
            ]);
    }

    private function markFailedOrRetrying(OutboundSyncOperation $operation, OutboundSyncResult $result): void
    {
        $attempts = $operation->attempts;
        $maxAttempts = max(1, $operation->maxAttempts);
        $hasAttemptsLeft = $attempts < $maxAttempts;
        $shouldRetry = $result->retryable && $hasAttemptsLeft;

        if ($shouldRetry) {
            $retryAt = now()->addSeconds($this->backoffSeconds($attempts));

            DB::table('shopify_outbound_syncs')
                ->where('id', $operation->id)
                ->update([
                    'status' => OutboundSyncStatus::RETRYING,
                    'available_at' => $retryAt,
                    'next_retry_at' => $retryAt,
                    'locked_at' => null,
                    'response_payload' => $this->encodeJson($result->responsePayload),
                    'last_error_code' => $result->errorCode,
                    'last_error_message' => $result->errorMessage,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('shopify_outbound_syncs')
            ->where('id', $operation->id)
            ->update([
                'status' => $hasAttemptsLeft ? OutboundSyncStatus::FAILED : OutboundSyncStatus::DEAD,
                'available_at' => null,
                'next_retry_at' => null,
                'locked_at' => null,
                'response_payload' => $this->encodeJson($result->responsePayload),
                'last_error_code' => $result->errorCode,
                'last_error_message' => $result->errorMessage,
                'updated_at' => now(),
            ]);
    }

    private function storeAttempt(
        OutboundSyncOperation $operation,
        OutboundSyncResult $result,
        mixed $startedAt,
        int $durationMs,
    ): void {
        DB::table('shopify_outbound_sync_attempts')->insert([
            'outbound_sync_id' => $operation->id,
            'attempt_number' => $operation->attempts,
            'status' => $result->success ? 'success' : 'failed',
            'started_at' => $startedAt,
            'finished_at' => now(),
            'duration_ms' => $durationMs,
            'http_status' => $result->httpStatus,
            'request_payload' => $this->encodeJson($operation->payload),
            'response_payload' => $this->encodeJson($result->responsePayload),
            'error_code' => $result->errorCode,
            'error_message' => $result->errorMessage,
            'retryable' => $result->retryable,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function resolveHandler(string $handlerClass): OutboundSyncHandlerInterface
    {
        $handler = app($handlerClass);

        if (!$handler instanceof OutboundSyncHandlerInterface) {
            throw new \RuntimeException("Invalid outbound sync handler [{$handlerClass}].");
        }

        return $handler;
    }

    private function isRetryableException(Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        foreach (['timeout', 'timed out', 'connection', '429', 'rate limit', '5xx', 'temporar'] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function backoffSeconds(int $attemptNumber): int
    {
        $attempt = max(1, $attemptNumber);
        $base = 15;
        $cap = 1800;
        $seconds = min($cap, $base * (2 ** ($attempt - 1)));

        return $seconds + random_int(0, 10);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function encodeJson(array $data): ?string
    {
        if ($data === []) {
            return null;
        }

        return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

