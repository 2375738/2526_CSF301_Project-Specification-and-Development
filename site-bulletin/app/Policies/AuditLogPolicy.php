<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\RoleScopeService;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('manager', 'ops_manager', 'hr', 'admin');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        $roleScope = app(RoleScopeService::class);

        if ($roleScope->canManageAllDepartments($user)) {
            return true;
        }

        if ($user->hasRole('manager')) {
            $auditable = $auditLog->auditable;

            if ($auditable instanceof \App\Models\Ticket) {
                return $roleScope->canManageDepartment($user, $auditable->department_id);
            }

            if ($auditable instanceof \App\Models\Conversation) {
                return $auditable->participants()
                    ->where('users.id', $user->id)
                    ->exists();
            }
        }

        return $auditLog->actor_id === $user->id;
    }
}
