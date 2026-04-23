<?php

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $table = 'webhook_logs';

    protected $fillable = [
        'store_id',
        'provider',
        'topic',
        'external_id',
        'payload',
        'status',
        'attempts',
        'error_message',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
