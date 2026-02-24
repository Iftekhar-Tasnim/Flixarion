<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'tmdb_id',
    ];

    protected function casts(): array
    {
        return [
            'tmdb_id' => 'integer',
        ];
    }

    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_genre');
    }
}
