<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceSample extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recorded_at',
        'units_per_hour',
        'quality_score',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'units_per_hour' => 'float',
        'quality_score' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
