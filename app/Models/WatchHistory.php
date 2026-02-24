<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchHistory extends Model
{
    public $timestamps = false;

    protected $table = 'watch_history';

    protected $fillable = [
        'user_id',
        'content_id',
        'episode_id',
        'is_completed',
        'played_at',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'played_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }
}
