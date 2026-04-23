<?php

namespace App\Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = [
        'store_id',
        'menu_id',
        'parent_id',
        'shopify_menu_item_id',
        'resource_id',
        'title',
        'type',
        'url',
        'tags',
        'position',
        'raw_payload',
    ];

    protected $casts = [
        'tags' => 'array',
        'position' => 'integer',
        'raw_payload' => 'array',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
