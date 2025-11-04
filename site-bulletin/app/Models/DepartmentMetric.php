<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'metric_date',
        'open_tickets',
        'sla_breaches',
        'messages_sent',
        'avg_first_response_minutes',
        'avg_resolution_minutes',
    ];

    protected $casts = [
        'metric_date' => 'date',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
