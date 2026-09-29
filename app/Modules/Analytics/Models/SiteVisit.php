<?php

namespace App\Modules\Analytics\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteVisit extends Model
{
    protected $table = 'site_visits';

    protected $guarded = ['id'];

    protected $casts = [
        'user_id' => 'integer',
        'page_views' => 'integer',
        'duration_seconds' => 'integer',
        'is_bounce' => 'boolean',
        'started_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
