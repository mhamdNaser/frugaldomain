<?php

namespace App\Modules\Core\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PartnerNotificationDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => (string) data_get($this->data, 'title', 'Notification'),
            'summary' => (string) data_get($this->data, 'summary', data_get($this->data, 'message', '')),
            'message' => (string) data_get($this->data, 'message', ''),
            'redirect_url' => data_get($this->data, 'redirect_url'),
            'data' => $this->data,
            'is_read' => (bool) $this->read_at,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
