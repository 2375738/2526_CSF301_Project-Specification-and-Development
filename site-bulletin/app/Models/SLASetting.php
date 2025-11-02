<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SLASetting extends Model
{
    /** @use HasFactory<\Database\Factories\SLASettingFactory> */
    use HasFactory;

    protected $fillable = [
        'priority',
        'first_response_minutes',
        'resolution_minutes',
        'pause_statuses',
    ];

    protected $casts = [
        'pause_statuses' => 'array',
    ];

    protected function pauseStatuses(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ?? [],
        );
    }
}
