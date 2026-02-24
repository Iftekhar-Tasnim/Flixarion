<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShadowContentSource extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'source_id',
        'raw_filename',
        'file_path',
        'file_extension',
        'file_size',
        'detected_encoding',
        'subtitle_paths',
        'scan_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'subtitle_paths' => 'array',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
