<?php

namespace App\Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;

class Metafield extends Model
{
    protected $table = 'metafields';

    protected $fillable = [
        'store_id',
        'shopify_metafield_id',
        'metafieldable_type',
        'metafieldable_id',
        'namespace',
        'key',
        'value',
        'type',
    ];

    protected $casts = [
        'value' => 'array', // 🔥 غيرها من json إلى array
    ];

    // 🔥 polymorphic relation
    public function metafieldable()
    {
        return $this->morphTo();
    }

    // 🔥 many-to-many مع metaobjects
    public function metaobjects()
    {
        return $this->belongsToMany(
            MetaObject::class,
            'metafield_metaobjects',
            'metafield_id',
            'metaobject_id'
        );
    }
}
