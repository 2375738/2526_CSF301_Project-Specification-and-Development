<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Announcement extends Model
{
    /** @use HasFactory<\Database\Factories\AnnouncementFactory> */
    use HasFactory;

    public const ACKNOWLEDGEMENT_UNDERSTOOD = 'understood';
    public const ACKNOWLEDGEMENT_NEEDS_CLARIFICATION = 'needs_clarification';

    protected $fillable = [
        'title',
        'body',
        'priority',
        'starts_at',
        'ends_at',
        'is_pinned',
        'is_active',
        'author_id',
        'department_id',
        'audience',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_pinned' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $announcement): void {
            if ($announcement->audience !== 'department') {
                $announcement->department_id = null;
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_reads')
            ->withPivot(['read_at', 'acknowledgement', 'acknowledged_at'])
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $q) {
                $now = Carbon::now();
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) {
                $now = Carbon::now();
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc('is_pinned')
            ->orderByDesc('starts_at')
            ->orderByDesc('created_at');
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        $deptIds = $user ? $user->departmentIds() : collect();

        return $query->where(function (Builder $q) use ($user, $deptIds) {
            $q->where('audience', 'all');

            if ($deptIds->isNotEmpty()) {
                $q->orWhere(function (Builder $inner) use ($deptIds) {
                    $inner->where('audience', 'department')
                        ->whereIn('department_id', $deptIds);
                });
            }

            if ($user && ($user->isManager() || $user->isHr() || $user->isOpsManager())) {
                $q->orWhere('audience', 'managers');
            }
        });
    }

    public function markReadFor(User $user): void
    {
        $this->syncReceiptFor($user, [
            'read_at' => now(),
        ]);
    }

    public function acknowledgeFor(User $user, string $acknowledgement): void
    {
        $this->syncReceiptFor($user, [
            'read_at' => now(),
            'acknowledgement' => $acknowledgement,
            'acknowledged_at' => now(),
        ]);
    }

    public function receiptFor(User $user): ?object
    {
        return DB::table('announcement_reads')
            ->where('announcement_id', $this->id)
            ->where('user_id', $user->id)
            ->first(['read_at', 'acknowledgement', 'acknowledged_at']);
    }

    public static function acknowledgementOptions(): array
    {
        return [
            self::ACKNOWLEDGEMENT_UNDERSTOOD,
            self::ACKNOWLEDGEMENT_NEEDS_CLARIFICATION,
        ];
    }

    protected function syncReceiptFor(User $user, array $attributes): void
    {
        $existing = $this->readers()
            ->where('users.id', $user->id)
            ->exists();

        if ($existing) {
            $this->readers()->updateExistingPivot($user->id, $attributes);
            return;
        }

        $this->readers()->syncWithoutDetaching([
            $user->id => $attributes,
        ]);
    }
}
