<?php

namespace App\Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    protected $fillable = [
        'store_id',
        'shopify_blog_id',
        'handle',
        'title',
        'seo_title',
        'seo_description',
        'template_suffix',
        'is_published',
        'published_at',
        'comment_policy',
        'tags',
        'raw_payload',
        'shopify_created_at',
        'shopify_updated_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'tags' => 'array',
        'raw_payload' => 'array',
        'shopify_created_at' => 'datetime',
        'shopify_updated_at' => 'datetime',
    ];

    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}
