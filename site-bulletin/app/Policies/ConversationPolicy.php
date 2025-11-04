<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    public function view(User $user, Conversation $conversation): bool
    {
        if (! $conversation->relationLoaded('participants')) {
            return $conversation->participants()->where('users.id', $user->id)->exists();
        }

        return $conversation->participants->contains(fn ($participant) => $participant->id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('employee', 'manager', 'ops_manager', 'hr', 'admin');
    }

    public function message(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation) && ! $conversation->is_locked;
    }

    public function lock(User $user, Conversation $conversation): bool
    {
        if (! $this->view($user, $conversation)) {
            return false;
        }

        if (! $conversation->relationLoaded('participants')) {
            return $conversation->participants()
                ->wherePivot('role', 'owner')
                ->where('users.id', $user->id)
                ->exists();
        }

        return $conversation->participants
            ->firstWhere('pivot.role', 'owner')?->id === $user->id;
    }
}
