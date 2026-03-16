<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'slug',
        'author',
        'body',
        'sources',
        'methodology',
        'status',
        'asset',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'sources' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
