<?php

namespace App\Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;

class MetaObject extends Model
{
    protected $table = 'metaobjects';

    protected $fillable = [
        'store_id',
        'shopify_metaobject_id',
        'type',
        'fields',
    ];

    protected $casts = [
        'fields' => 'array', // 🔥 مهم
    ];

    // 🔥 علاقة many-to-many مع metafields
    public function metafields()
    {
        return $this->belongsToMany(
            Metafield::class,
            'metafield_metaobjects',
            'metaobject_id',
            'metafield_id'
        );
    }
}
