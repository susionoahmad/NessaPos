<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionContent extends Model
{
    protected $fillable = ['section_id', 'type', 'key', 'value'];

    protected $casts = [
        'value' => 'array',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
