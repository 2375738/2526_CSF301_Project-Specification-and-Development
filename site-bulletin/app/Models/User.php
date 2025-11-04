<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use App\Models\SavedAnalyticsView;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'primary_department_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function requestedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assignee_id');
    }

    public function performanceSnapshots(): HasMany
    {
        return $this->hasMany(PerformanceSnapshot::class);
    }

    public function primaryDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'primary_department_id');
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class)
            ->withPivot(['role', 'is_primary'])
            ->withTimestamps();
    }

    public function managedDepartments(): BelongsToMany
    {
        return $this->departments()->wherePivotIn('role', ['manager', 'hr_manager']);
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['role', 'last_read_at'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function managerRelationships(): HasMany
    {
        return $this->hasMany(ManagerRelationship::class, 'manager_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    public function submittedRoleChangeRequests(): HasMany
    {
        return $this->hasMany(RoleChangeRequest::class, 'requester_id');
    }

    public function savedAnalyticsViews(): HasMany
    {
        return $this->hasMany(SavedAnalyticsView::class);
    }

    public function roleChangeRequests(): HasMany
    {
        return $this->hasMany(RoleChangeRequest::class, 'target_user_id');
    }

    public function reportsToRelationships(): HasMany
    {
        return $this->hasMany(ManagerRelationship::class, 'reports_to_id');
    }

    public function departmentIds(): Collection
    {
        $ids = $this->departments()->pluck('departments.id');

        if ($this->primary_department_id) {
            $ids->push($this->primary_department_id);
        }

        return $ids->unique()->values();
    }

    public function scopeRole($query, UserRole|string $role)
    {
        $value = $role instanceof UserRole ? $role->value : $role;

        return $query->where('role', $value);
    }

    public function hasRole(UserRole|string ...$roles): bool
    {
        $current = $this->role instanceof UserRole ? $this->role->value : $this->role;

        foreach ($roles as $role) {
            $value = $role instanceof UserRole ? $role->value : $role;

            if ($current === $value) {
                return true;
            }
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isManager(): bool
    {
        return $this->hasRole(UserRole::Manager, UserRole::OpsManager);
    }

    public function isEmployee(): bool
    {
        return $this->role === UserRole::Employee;
    }

    public function isHr(): bool
    {
        return $this->role === UserRole::Hr;
    }

    public function isOpsManager(): bool
    {
        return $this->role === UserRole::OpsManager;
    }
}
