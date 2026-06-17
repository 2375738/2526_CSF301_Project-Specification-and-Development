<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use App\Services\RoleScopeService;

class TicketPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('employee', 'manager', 'ops_manager', 'hr', 'admin');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($this->canActOnTicket($user, $ticket)) {
            return true;
        }

        if (
            $ticket->requester_id === $user->id
            || $ticket->assignee_id === $user->id
            || $ticket->created_for_id === $user->id
        ) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('employee', 'manager', 'ops_manager', 'hr');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $this->canActOnTicket($user, $ticket);
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        if ($this->canActOnTicket($user, $ticket)) {
            return true;
        }

        return $ticket->requester_id === $user->id || $ticket->created_for_id === $user->id;
    }

    public function upload(User $user, Ticket $ticket): bool
    {
        return $this->comment($user, $ticket);
    }

    public function close(User $user, Ticket $ticket): bool
    {
        if ($this->canActOnTicket($user, $ticket)) {
            return true;
        }

        return $ticket->requester_id === $user->id || $ticket->created_for_id === $user->id;
    }

    protected function canActOnTicket(User $user, Ticket $ticket): bool
    {
        if (! $user->hasRole('manager', 'ops_manager', 'hr', 'admin')) {
            return false;
        }

        if ($ticket->isPrivateTo($user)) {
            return false;
        }

        return app(RoleScopeService::class)->canViewDepartment($user, $ticket->department_id);
    }
}
