<?php

namespace App\Modules\Orders\Models;

use App\Modules\User\Models\Customer;
use Illuminate\Database\Eloquent\Model;

class DraftOrder extends Model
{
    protected $fillable = [
        'store_id',
        'customer_id',
        'shopify_customer_id',
        'shopify_draft_order_id',
        'name',
        'status',
        'invoice_url',
        'subtotal',
        'tax',
        'total',
        'currency',
        'completed_at',
        'raw_payload',
        'shopify_created_at',
        'shopify_updated_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'completed_at' => 'datetime',
        'raw_payload' => 'array',
        'shopify_created_at' => 'datetime',
        'shopify_updated_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(DraftOrderItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
