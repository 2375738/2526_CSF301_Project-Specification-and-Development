<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceRollup extends Model
{
    protected $fillable = [
        'user_id',
        'bucket_type',
        'bucket_start',
        'sample_count',
        'avg_units_per_hour',
        'avg_quality_score',
    ];

    protected $casts = [
        'bucket_start' => 'datetime',
        'sample_count' => 'integer',
        'avg_units_per_hour' => 'float',
        'avg_quality_score' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
