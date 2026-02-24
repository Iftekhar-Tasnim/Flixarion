<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Content extends Model
{
    use HasFactory;
    protected $fillable = [
        'tmdb_id',
        'imdb_id',
        'type',
        'title',
        'original_title',
        'year',
        'description',
        'poster_path',
        'backdrop_path',
        'cast',
        'director',
        'rating',
        'vote_count',
        'runtime',
        'trailer_url',
        'alternative_titles',
        'language',
        'status',
        'enrichment_status',
        'confidence_score',
        'is_published',
        'is_featured',
        'watch_count',
    ];

    protected function casts(): array
    {
        return [
            'tmdb_id' => 'integer',
            'year' => 'integer',
            'cast' => 'array',
            'alternative_titles' => 'array',
            'rating' => 'decimal:1',
            'confidence_score' => 'decimal:2',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'watch_count' => 'integer',
        ];
    }

    // ── Relationships ──

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'content_genre');
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class)->orderBy('season_number');
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class);
    }

    public function sourceLinks(): MorphMany
    {
        return $this->morphMany(SourceLink::class, 'linkable');
    }

    public function watchlists(): HasMany
    {
        return $this->hasMany(Watchlist::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function watchHistory(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // ── Scopes ──

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeMovies($query)
    {
        return $query->where('type', 'movie');
    }

    public function scopeSeries($query)
    {
        return $query->where('type', 'series');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
