<?php

namespace App\Modules\Stores\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreSetting extends Model
{
    protected $fillable = [
        'store_id',
        'allow_guest_checkout',
        'enable_cod',
        'enable_stripe',
        'tax_included',
        'default_language',
        'push_notifications_enabled',
        'extra_settings',
    ];

    protected $casts = [
        'allow_guest_checkout' => 'boolean',
        'enable_cod' => 'boolean',
        'enable_stripe' => 'boolean',
        'tax_included' => 'boolean',
        'push_notifications_enabled' => 'boolean',
        'extra_settings' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
