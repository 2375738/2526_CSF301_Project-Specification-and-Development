<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketApproval extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_NEEDS_INFO = 'needs_info';

    protected $fillable = [
        'ticket_id',
        'step_order',
        'step_key',
        'approver_role',
        'status',
        'approver_id',
        'public_note',
        'internal_note',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function markDecision(string $status, User $approver, ?string $publicNote = null, ?string $internalNote = null): void
    {
        $this->forceFill([
            'status' => $status,
            'approver_id' => $approver->id,
            'public_note' => $publicNote,
            'internal_note' => $internalNote,
            'decided_at' => now(),
        ])->save();
    }

    public function publicStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_NEEDS_INFO => 'More information needed',
            self::STATUS_QUEUED => 'Queued for later review',
            default => 'Pending approval',
        };
    }
}
