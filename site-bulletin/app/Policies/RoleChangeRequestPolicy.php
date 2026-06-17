<?php

namespace App\Policies;

use App\Models\RoleChangeRequest;
use App\Models\User;
use App\Services\RoleScopeService;

class RoleChangeRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('manager', 'ops_manager', 'hr', 'admin');
    }

    public function view(User $user, RoleChangeRequest $request): bool
    {
        if (app(RoleScopeService::class)->canManageAllDepartments($user)) {
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
        return app(RoleScopeService::class)->canManageAllDepartments($user);
    }
}
