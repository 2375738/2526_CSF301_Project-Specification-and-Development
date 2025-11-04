<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'type',
        'creator_id',
        'department_id',
        'is_locked',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['role', 'last_read_at'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function scopeForUser($query, User $user)
    {
        return $query->whereHas('participants', fn ($q) => $q->where('conversation_participants.user_id', $user->id));
    }

    public function markReadFor(User $user): void
    {
        $this->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);
    }

    public function unreadCountFor(User $user): int
    {
        $pivot = $this->participants
            ->firstWhere('id', $user->id);

        $lastRead = $pivot?->pivot?->last_read_at;

        return $this->messages()
            ->when($lastRead, fn ($q) => $q->where('created_at', '>', $lastRead))
            ->where('sender_id', '!=', $user->id)
            ->count();
    }
}
