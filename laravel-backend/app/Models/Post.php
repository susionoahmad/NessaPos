<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'thumbnail',
        'meta_title',
        'meta_description',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function categories()
    {
        return $this->belongsToMany(BlogCategory::class, 'post_categories', 'post_id', 'category_id');
    }

    public function affiliates()
    {
        return $this->belongsToMany(AffiliateLink::class, 'post_affiliates', 'post_id', 'affiliate_id');
    }
}
