<?php

namespace App\Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;

class MetaFieldMetaObject extends Model
{
    protected $table = 'metafield_metaobjects';

    protected $fillable = [
        'metafield_id',
        'metaobject_id',
    ];

    public $timestamps = false;

    public function metafield()
    {
        return $this->belongsTo(Metafield::class);
    }

    public function metaobject()
    {
        return $this->belongsTo(MetaObject::class);
    }
}
