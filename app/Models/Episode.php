<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Episode extends Model
{
    protected $fillable = [
        'season_id',
        'content_id',
        'episode_number',
        'title',
        'tmdb_episode_id',
        'overview',
        'still_path',
        'runtime',
        'air_date',
    ];

    protected function casts(): array
    {
        return [
            'episode_number' => 'integer',
            'runtime' => 'integer',
            'air_date' => 'date',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function sourceLinks(): MorphMany
    {
        return $this->morphMany(SourceLink::class, 'linkable');
    }
}
