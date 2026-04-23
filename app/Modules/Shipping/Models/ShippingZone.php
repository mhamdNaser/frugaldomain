<?php

namespace App\Modules\Shipping\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    protected $table = 'shipping_zones';

    protected $fillable = [
        'store_id',
        'shopify_zone_id',
        'shopify_profile_id',
        'name',
        'countries',
        'raw_payload',
    ];

    protected $casts = [
        'countries' => 'array',
        'raw_payload' => 'array',
    ];

    public function methods()
    {
        return $this->hasMany(ShippingMethod::class, 'shipping_zone_id');
    }

    public function rates()
    {
        return $this->hasMany(ShippingRate::class, 'shipping_zone_id');
    }
}
