<?php

namespace App\Modules\Shipping\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    protected $table = 'shipping_rates';

    protected $fillable = [
        'store_id',
        'shipping_zone_id',
        'shipping_method_id',
        'shopify_zone_id',
        'shopify_method_id',
        'shopify_rate_id',
        'name',
        'amount',
        'currency',
        'raw_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'raw_payload' => 'array',
    ];

    public function zone()
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function method()
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }
}
