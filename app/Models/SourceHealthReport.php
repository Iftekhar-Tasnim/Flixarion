<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceHealthReport extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'source_id',
        'isp_name',
        'is_reachable',
        'response_time_ms',
        'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'is_reachable' => 'boolean',
            'response_time_ms' => 'integer',
            'reported_at' => 'datetime',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
