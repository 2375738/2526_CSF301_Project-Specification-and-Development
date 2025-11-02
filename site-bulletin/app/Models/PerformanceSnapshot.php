<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceSnapshot extends Model
{
    /** @use HasFactory<\Database\Factories\PerformanceSnapshotFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'week_start',
        'units_per_hour',
        'rank_percentile',
    ];

    protected $casts = [
        'week_start' => 'date',
        'units_per_hour' => 'integer',
        'rank_percentile' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeRecent($query, int $weeks = 6)
    {
        return $query->orderByDesc('week_start')->limit($weeks);
    }
}
