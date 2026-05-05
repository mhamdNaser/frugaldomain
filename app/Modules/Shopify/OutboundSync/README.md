# Shopify Outbound Sync

This folder contains the Local -> Shopify outbound pipeline.

## What It Includes

- Queueing outbound operations with idempotency.
- Processing operations through dedicated handlers.
- Retry/backoff logic with terminal status (`dead`) after max attempts.
- Per-attempt logging for observability.
- API endpoints for queue/list/retry/dispatch due operations.
- Artisan command:
  - `php artisan shopify:outbound-dispatch-due --limit=100`

## Default Handler

- `GenericGraphqlOutboundSyncHandler` expects payload:
  - `mutation` or `query` (GraphQL string)
  - `variables` (optional array)
  - `user_errors_path` (optional dot path)
  - `resource_path` (optional dot path to Shopify resource id)

## Status Lifecycle

- `pending` -> `processing` -> `synced`
- `pending`/`processing` -> `retrying` -> `processing`
- Terminal failures:
  - `failed` (non-retryable and attempts remain)
  - `dead` (max attempts reached)

