<?php

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookSubscription extends Model
{
    protected $table = 'webhook_subscriptions';

    protected $fillable = [
        'store_id',
        'shopify_webhook_id',
        'event',
        'topic',
        'callback_url',
        'endpoint_type',
        'format',
        'is_active',
        'provider',
        'raw_payload',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'raw_payload' => 'array',
    ];
}

