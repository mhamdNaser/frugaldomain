<?php

namespace App\Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = [
        'store_id',
        'shopify_menu_id',
        'handle',
        'title',
        'items_count',
        'raw_payload',
    ];

    protected $casts = [
        'items_count' => 'integer',
        'raw_payload' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(MenuItem::class);
    }
}
