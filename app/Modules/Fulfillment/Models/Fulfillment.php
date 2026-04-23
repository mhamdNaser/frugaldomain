<?php

namespace App\Modules\Fulfillment\Models;

use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;

class Fulfillment extends Model
{
    protected $table = 'fulfillments';

    protected $fillable = [
        'store_id',
        'order_id',
        'fulfillment_service_id',
        'shopify_fulfillment_id',
        'shopify_order_id',
        'name',
        'status',
        'shipment_status',
        'tracking_company',
        'tracking_number',
        'tracking_url',
        'raw_payload',
        'shopify_created_at',
        'shopify_updated_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'shopify_created_at' => 'datetime',
        'shopify_updated_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(FulfillmentItem::class);
    }

    public function tracking()
    {
        return $this->hasMany(FulfillmentTracking::class);
    }

    public function service()
    {
        return $this->belongsTo(FulfillmentService::class, 'fulfillment_service_id');
    }
}
