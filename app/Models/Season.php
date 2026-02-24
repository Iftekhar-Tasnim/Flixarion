<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    protected $fillable = [
        'content_id',
        'season_number',
        'tmdb_season_id',
        'title',
        'poster_path',
        'overview',
        'episode_count',
        'air_date',
    ];

    protected function casts(): array
    {
        return [
            'season_number' => 'integer',
            'episode_count' => 'integer',
            'air_date' => 'date',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderBy('episode_number');
    }
}
