<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Download extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'version',
        'file_path',
        'platform',
        'description',
        'is_active',
    ];

    protected $appends = ['download_url'];

    public function getDownloadUrlAttribute()
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }
}
