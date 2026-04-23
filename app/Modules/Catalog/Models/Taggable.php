<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class Taggable extends Model
{
    protected $table = 'taggables';

    protected $fillable = [
        'tag_id',
        'taggable_type',
        'taggable_id',
    ];

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }
}
