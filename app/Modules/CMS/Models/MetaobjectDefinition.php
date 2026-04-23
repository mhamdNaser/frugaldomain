<?php

namespace App\Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;

class MetaobjectDefinition extends Model
{
    protected $fillable = [
        'store_id',
        'shopify_metaobject_definition_id',
        'type',
        'name',
        'display_name_key',
        'access',
        'capabilities',
        'raw_payload',
    ];

    protected $casts = [
        'access' => 'array',
        'capabilities' => 'array',
        'raw_payload' => 'array',
    ];
}

