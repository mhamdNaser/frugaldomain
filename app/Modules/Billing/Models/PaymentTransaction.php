<?php

namespace App\Modules\Billing\Models;

use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'order_id',
        'refund_id',
        'shopify_transaction_id',
        'parent_shopify_transaction_id',
        'gateway',
        'account_number',
        'transaction_reference',
        'kind',
        'amount',
        'currency',
        'status',
        'test',
        'manual_payment_gateway',
        'processed_at',
        'raw_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'test' => 'boolean',
        'manual_payment_gateway' => 'boolean',
        'processed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function refund()
    {
        return $this->belongsTo(Refund::class);
    }
}
