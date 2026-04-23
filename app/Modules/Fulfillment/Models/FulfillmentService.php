<?php

namespace App\Modules\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;

class FulfillmentService extends Model
{
    protected $table = 'fulfillment_services';

    protected $fillable = [
        'store_id',
        'shopify_fulfillment_service_id',
        'name',
        'email',
        'service_name',
        'type',
        'callback_url',
        'raw_payload',
    ];

    protected $casts = [
        'callback_url' => 'boolean',
        'raw_payload' => 'array',
    ];

    public function fulfillments()
    {
        return $this->hasMany(Fulfillment::class, 'fulfillment_service_id');
    }

    public function fulfillmentOrders()
    {
        return $this->hasMany(FulfillmentOrder::class, 'fulfillment_service_id');
    }
}
