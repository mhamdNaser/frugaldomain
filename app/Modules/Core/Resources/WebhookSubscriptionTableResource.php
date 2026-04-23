<?php

namespace App\Modules\Core\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WebhookSubscriptionTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'shopify_webhook_id' => $this->shopify_webhook_id,
            'event' => $this->event,
            'topic' => $this->topic,
            'callback_url' => $this->callback_url,
            'endpoint_type' => $this->endpoint_type,
            'format' => $this->format,
            'is_active' => $this->is_active,
            'provider' => $this->provider,
            'raw_payload' => $this->raw_payload,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

