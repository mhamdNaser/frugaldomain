<?php

namespace App\Modules\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;

class FulfillmentTracking extends Model
{
    protected $table = 'fulfillment_tracking';

    protected $fillable = [
        'store_id',
        'fulfillment_id',
        'company',
        'number',
        'url',
        'raw_payload',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];

    public function fulfillment()
    {
        return $this->belongsTo(Fulfillment::class);
    }
}
