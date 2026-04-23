<?php

namespace App\Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = [
        'store_id',
        'article_id',
        'shopify_comment_id',
        'author',
        'email',
        'ip',
        'status',
        'body',
        'raw_payload',
        'published_at',
        'shopify_created_at',
        'shopify_updated_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'published_at' => 'datetime',
        'shopify_created_at' => 'datetime',
        'shopify_updated_at' => 'datetime',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
