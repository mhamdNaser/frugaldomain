<?php

namespace App\Modules\Analytics\Models;

use Illuminate\Database\Eloquent\Model;

class SitePageView extends Model
{
    protected $table = 'site_page_views';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];
}
