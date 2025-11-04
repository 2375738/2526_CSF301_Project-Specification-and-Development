<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('manager', 'ops_manager', 'hr', 'admin');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        if ($user->hasRole('hr', 'admin')) {
            return true;
        }

        if ($user->hasRole('manager', 'ops_manager')) {
            $auditable = $auditLog->auditable;

            if ($auditable instanceof \App\Models\Ticket) {
                return $user->managedDepartments()
                    ->where('departments.id', $auditable->department_id)
                    ->exists();
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
