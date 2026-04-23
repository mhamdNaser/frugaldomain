<?php

namespace App\Modules\Core\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WebhookLogTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'provider' => $this->provider,
            'topic' => $this->topic,
            'external_id' => $this->external_id,
            'status' => $this->status,
            'attempts' => $this->attempts,
            'error_message' => $this->error_message,
            'payload' => $this->payload,
            'received_at' => $this->received_at,
            'processed_at' => $this->processed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

