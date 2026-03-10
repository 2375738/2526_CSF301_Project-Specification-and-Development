<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeSnippet extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'summary',
        'department_id',
        'audience',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        $deptIds = $user ? $user->departmentIds() : collect();
        $isManager = $user && ($user->isManager() || $user->isHr() || $user->isOpsManager());

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
