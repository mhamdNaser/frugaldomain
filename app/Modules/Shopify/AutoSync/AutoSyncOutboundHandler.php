<?php

namespace App\Modules\Shopify\AutoSync;

use App\Modules\Shopify\AutoSync\Exceptions\ShopifyUserErrorsException;
use App\Modules\Shopify\OutboundSync\Contracts\OutboundSyncHandlerInterface;
use App\Modules\Shopify\OutboundSync\DTOs\OutboundSyncOperation;
use App\Modules\Shopify\OutboundSync\DTOs\OutboundSyncResult;
use App\Modules\Stores\Models\Store;
use Throwable;

/**
 * Retries an auto-sync push that could not reach Shopify during the request.
 */
class AutoSyncOutboundHandler implements OutboundSyncHandlerInterface
{
    public function __construct(
        private readonly ShopifyAutoSync $autoSync,
    ) {}

    public function handle(Store $store, OutboundSyncOperation $operation): OutboundSyncResult
    {
        $spec = $operation->payload['auto_sync'] ?? null;
        $syncer = is_array($spec) ? $this->autoSync->syncerFor((string) ($spec['model'] ?? '')) : null;

        if (!$syncer) {
            return OutboundSyncResult::failure('invalid_payload', 'Unknown auto-sync payload.', retryable: false);
        }

        $action = (string) $spec['action'];
        $before = is_array($spec['before'] ?? null) ? $spec['before'] : [];
        $changed = is_array($spec['changed'] ?? null) ? $spec['changed'] : [];
        $ctx = new SyncContext(new ShopifyGateway($store), $this->autoSync);

        try {
            if ($action === 'delete') {
                $syncer->delete($before, $ctx);
            } else {
                $model = $spec['model']::query()->find($spec['id']);

                if (!$model) {
                    return OutboundSyncResult::success(['skipped' => 'Local record no longer exists.']);
                }

                $action === 'create'
                    ? $syncer->create($model, $ctx)
                    : $syncer->update($model, $before, $changed, $ctx);
            }
        } catch (ShopifyUserErrorsException $e) {
            return OutboundSyncResult::failure('shopify_user_errors', $e->getMessage(), retryable: false, httpStatus: 200);
        } catch (Throwable $e) {
            return OutboundSyncResult::fromThrowable($e, ShopifyAutoSync::isRetryable($e));
        }

        return OutboundSyncResult::success(['notes' => $ctx->notes()]);
    }
}
