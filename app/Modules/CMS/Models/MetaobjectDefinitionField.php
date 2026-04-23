<?php

namespace App\Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;

class MetaobjectDefinitionField extends Model
{
    protected $fillable = [
        'metaobject_definition_id',
        'field_key',
        'name',
        'type',
        'required',
        'validations',
        'raw_payload',
    ];

    protected $casts = [
        'required' => 'boolean',
        'validations' => 'array',
        'raw_payload' => 'array',
    ];
}

