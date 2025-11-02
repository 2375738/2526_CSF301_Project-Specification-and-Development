<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticket extends Model
{
    /** @use HasFactory<\Database\Factories\TicketFactory> */
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'assignee_id',
        'category_id',
        'duplicate_of_id',
        'priority',
        'status',
        'title',
        'description',
        'location',
        'closed_at',
    ];

    protected $casts = [
        'priority' => TicketPriority::class,
        'status' => TicketStatus::class,
        'closed_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->orderBy('created_at');
    }

    public function publicComments(): HasMany
    {
        return $this->comments()->where('is_private', false);
    }

    public function privateComments(): HasMany
    {
        return $this->comments()->where('is_private', true);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function statusChanges(): HasMany
    {
        return $this->hasMany(TicketStatusChange::class)->orderBy('created_at');
    }

    public function latestStatusChange(): HasOne
    {
        return $this->hasOne(TicketStatusChange::class)->latestOfMany();
    }

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'duplicate_of_id');
    }

    public function duplicates(): HasMany
    {
        return $this->hasMany(Ticket::class, 'duplicate_of_id');
    }

    public function scopeStatus($query, TicketStatus|string $status)
    {
        $value = $status instanceof TicketStatus ? $status->value : $status;

        return $query->where('status', $value);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', array_map(fn ($status) => $status->value, TicketStatus::open()));
    }

    public function scopeOrderedForQueue($query)
    {
        return $query
            ->with(['requester', 'assignee', 'category', 'duplicateOf'])
            ->orderByRaw("FIELD(priority, 'critical','high','medium','low')")
            ->orderByDesc('created_at');
    }

    public function markStatus(TicketStatus $to, ?User $by = null, ?string $reason = null): void
    {
        $from = $this->status;

        $this->status = $to;

        if ($to === TicketStatus::Closed) {
            $this->closed_at = now();
        } elseif ($to !== TicketStatus::Resolved) {
            $this->closed_at = null;
        }

        $this->save();

        $this->statusChanges()->create([
            'user_id' => $by?->id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'reason' => $reason,
        ]);
    }

    public function markDuplicateOf(Ticket $primary, ?User $by = null, ?string $reason = null): void
    {
        $this->duplicate_of_id = $primary->id;

        $this->markStatus(
            TicketStatus::Cancelled,
            $by,
            $reason ?? 'Marked as duplicate of #' . $primary->id
        );
    }

    public function clearDuplicate(?User $by = null): void
    {
        $this->duplicate_of_id = null;
        $this->markStatus(TicketStatus::Reopened, $by, 'Reopened from duplicate state');
    }

    public function isPrivateTo(User $user): bool
    {
        if (! $this->category) {
            return false;
        }

        return $this->category->is_sensitive && ! $user->hasRole('manager', 'admin');
    }
}
