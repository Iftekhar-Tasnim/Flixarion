<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SourceLink extends Model
{
    protected $fillable = [
        'linkable_type',
        'linkable_id',
        'source_id',
        'file_path',
        'quality',
        'file_size',
        'codec_info',
        'part_number',
        'subtitle_paths',
        'status',
        'last_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'part_number' => 'integer',
            'subtitle_paths' => 'array',
            'last_verified_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBroken($query)
    {
        return $query->where('status', 'broken');
    }
}
