<?php

namespace App\Modules\Orders\Models;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\User\Models\Customer;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'store_id',
        'customer_id',
        'shopify_customer_id',
        'email',
        'shopify_order_id',
        'order_number',
        'status',
        'payment_status',
        'fulfillment_status',
        'subtotal',
        'tax',
        'shipping',
        'discount',
        'total',
        'currency',
        'placed_at',
        'raw_payload',
        'shopify_created_at',
        'shopify_updated_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'placed_at' => 'datetime',
        'raw_payload' => 'array',
        'shopify_created_at' => 'datetime',
        'shopify_updated_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function channel()
    {
        return $this->hasOne(OrderChannel::class);
    }

    public function risks()
    {
        return $this->hasMany(OrderRisk::class)
            ->orderByDesc('assessed_at')
            ->orderByDesc('id');
    }

    public function latestRisk()
    {
        return $this->hasOne(OrderRisk::class)
            ->orderByDesc('assessed_at')
            ->orderByDesc('id');
    }
}
