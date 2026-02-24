<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceScanLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'source_id',
        'phase',
        'status',
        'items_found',
        'items_matched',
        'items_failed',
        'error_log',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'items_found' => 'integer',
            'items_matched' => 'integer',
            'items_failed' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
