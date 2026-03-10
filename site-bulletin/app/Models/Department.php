<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'ops_code',
        'planned_headcount',
        'target_units_per_hour',
        'target_quality_pct',
        'day_shift_start',
        'day_shift_end',
        'night_shift_start',
        'night_shift_end',
    ];

    protected $casts = [
        'planned_headcount' => 'integer',
        'target_units_per_hour' => 'float',
        'target_quality_pct' => 'float',
        'day_shift_start' => 'datetime:H:i',
        'day_shift_end' => 'datetime:H:i',
        'night_shift_start' => 'datetime:H:i',
        'night_shift_end' => 'datetime:H:i',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'is_primary'])
            ->withTimestamps();
    }

    public function managers(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'manager');
    }

    public function hrManagers(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'hr_manager');
    }
}
