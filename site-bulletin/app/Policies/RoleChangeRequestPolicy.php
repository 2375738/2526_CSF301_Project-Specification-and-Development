<?php

namespace App\Policies;

use App\Models\RoleChangeRequest;
use App\Models\User;

class RoleChangeRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('manager', 'ops_manager', 'hr', 'admin');
    }

    public function view(User $user, RoleChangeRequest $request): bool
    {
        if ($user->hasRole('hr', 'admin')) {
            return true;
        }

        return $request->requester_id === $user->id || $request->target_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('employee', 'manager', 'ops_manager', 'hr', 'admin');
    }

    public function approve(User $user, RoleChangeRequest $request): bool
    {
        return $user->hasRole('hr', 'admin');
    }
}
