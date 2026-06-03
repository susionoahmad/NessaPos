<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['page_id', 'name', 'order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function contents()
    {
        return $this->hasMany(SectionContent::class);
    }
}
