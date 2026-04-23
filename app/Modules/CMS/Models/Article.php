<?php

namespace App\Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'store_id',
        'blog_id',
        'shopify_article_id',
        'handle',
        'title',
        'seo_title',
        'seo_description',
        'template_suffix',
        'is_published',
        'published_at',
        'author_name',
        'body',
        'summary',
        'tags',
        'comments_count',
        'raw_payload',
        'shopify_created_at',
        'shopify_updated_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'tags' => 'array',
        'comments_count' => 'integer',
        'raw_payload' => 'array',
        'shopify_created_at' => 'datetime',
        'shopify_updated_at' => 'datetime',
    ];

    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
