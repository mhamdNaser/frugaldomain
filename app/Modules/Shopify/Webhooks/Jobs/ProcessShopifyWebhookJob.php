<?php

namespace App\Modules\Shopify\Webhooks\Jobs;

use App\Modules\Core\Models\WebhookLog;
use App\Modules\Shopify\Webhooks\DTOs\WebhookData;
use App\Modules\Shopify\Webhooks\Services\ShopifyWebhookRouter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessShopifyWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly int $webhookLogId,
    ) {
        $this->onQueue('default');
    }

    public function handle(ShopifyWebhookRouter $router): void
    {
        $log = WebhookLog::query()->find($this->webhookLogId);

        if (!$log) {
            return;
        }

        if ($log->status === 'processed') {
            return;
        }

        $log->update([
            'status' => 'processing',
            'attempts' => (int) $log->attempts + 1,
        ]);

        try {
            $payload = json_decode((string) $log->payload, true);

            $data = new WebhookData(
                storeId: $log->store_id,
                provider: $log->provider,
                topic: $log->topic,
                externalId: $log->external_id,
                shopDomain: null,
                payload: is_array($payload) ? $payload : null,
                rawBody: (string) $log->payload,
                headers: [],
                hmacHeader: null,
            );

            $handler = $router->resolve($log->topic);

            if ($handler) {
                $handler->handle($data);
            }

            $log->update([
                'status' => 'processed',
                'processed_at' => now(),
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

