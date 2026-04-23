<?php

namespace App\Modules\Shipping\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    protected $table = 'shipping_methods';

    protected $fillable = [
        'store_id',
        'shipping_zone_id',
        'shopify_zone_id',
        'shopify_method_id',
        'name',
        'description',
        'is_active',
        'method_type',
        'conditions',
        'raw_payload',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'conditions' => 'array',
        'raw_payload' => 'array',
    ];

    public function zone()
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function rates()
    {
        return $this->hasMany(ShippingRate::class, 'shipping_method_id');
    }
}
