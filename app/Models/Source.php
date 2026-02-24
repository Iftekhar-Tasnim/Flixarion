<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'base_url',
        'scraper_type',
        'config',
        'is_active',
        'health_score',
        'priority',
        'last_scan_at',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
            'health_score' => 'decimal:2',
            'priority' => 'integer',
            'last_scan_at' => 'datetime',
        ];
    }

    public function sourceLinks(): HasMany
    {
        return $this->hasMany(SourceLink::class);
    }

    public function scanLogs(): HasMany
    {
        return $this->hasMany(SourceScanLog::class)->orderByDesc('started_at');
    }

    public function latestScanLog()
    {
        return $this->hasOne(SourceScanLog::class)->latestOfMany('started_at');
    }

    public function healthReports(): HasMany
    {
        return $this->hasMany(SourceHealthReport::class);
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
