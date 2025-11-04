<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleChangeRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'requester_id',
        'target_user_id',
        'requested_role',
        'department_id',
        'justification',
        'status',
        'approver_id',
        'decision_notes',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requestedRoleLabel(): string
    {
        $enum = UserRole::tryFrom($this->requested_role);

        return $enum ? ucfirst(str_replace('_', ' ', $enum->value)) : ucfirst($this->requested_role);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function markApproved(User $approver, ?string $notes = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_APPROVED,
            'approver_id' => $approver->id,
            'decision_notes' => $notes,
            'decided_at' => now(),
        ])->save();
    }

    public function markRejected(User $approver, ?string $notes = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_REJECTED,
            'approver_id' => $approver->id,
            'decision_notes' => $notes,
            'decided_at' => now(),
        ])->save();
    }
}
