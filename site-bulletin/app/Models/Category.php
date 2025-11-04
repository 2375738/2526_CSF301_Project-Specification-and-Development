<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'order',
        'is_sensitive',
        'department_id',
        'audience',
    ];

    protected $casts = [
        'is_sensitive' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            if ($category->audience !== 'department') {
                $category->department_id = null;
            }
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(Link::class)->orderBy('order');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function scopePublic($query)
    {
        return $query->where('is_sensitive', false);
    }

    public function scopeVisibleTo(Builder $query, ?User $user)
    {
        $deptIds = $user ? $user->departmentIds() : collect();
        $isManager = $user && ($user->isManager() || $user->isHr() || $user->isOpsManager());

        if (! $isManager) {
            $query->where('is_sensitive', false);
        }

        return $query->where(function (Builder $q) use ($deptIds, $isManager) {
            $q->where('audience', 'all');

            if ($deptIds->isNotEmpty()) {
                $q->orWhere(function (Builder $inner) use ($deptIds) {
                    $inner->where('audience', 'department')
                        ->whereIn('department_id', $deptIds);
                });
            }

            if ($isManager) {
                $q->orWhere('audience', 'managers');
            }
        });
    }
}
