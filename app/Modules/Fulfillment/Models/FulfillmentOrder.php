<?php

namespace App\Modules\Fulfillment\Models;

use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;

class FulfillmentOrder extends Model
{
    protected $table = 'fulfillment_orders';

    protected $fillable = [
        'store_id',
        'order_id',
        'fulfillment_service_id',
        'shopify_fulfillment_order_id',
        'shopify_order_id',
        'shopify_assigned_location_id',
        'assigned_location_name',
        'status',
        'request_status',
        'fulfill_at',
        'fulfill_by',
        'destination',
        'delivery_method',
        'raw_payload',
        'shopify_created_at',
        'shopify_updated_at',
    ];

    protected $casts = [
        'destination' => 'array',
        'delivery_method' => 'array',
        'raw_payload' => 'array',
        'fulfill_at' => 'datetime',
        'fulfill_by' => 'datetime',
        'shopify_created_at' => 'datetime',
        'shopify_updated_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(FulfillmentOrderItem::class);
    }

    public function service()
    {
        return $this->belongsTo(FulfillmentService::class, 'fulfillment_service_id');
    }
}
