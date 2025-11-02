<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

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
        return $user->hasRole('employee', 'manager', 'admin');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isManager()) {
            return ! $ticket->isPrivateTo($user);
        }

        if ($ticket->requester_id === $user->id || $ticket->assignee_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('employee', 'manager');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->isManager()) {
            return ! $ticket->isPrivateTo($user);
        }

        return false;
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        if ($user->isManager()) {
            return ! $ticket->isPrivateTo($user);
        }

        return $ticket->requester_id === $user->id;
    }

    public function upload(User $user, Ticket $ticket): bool
    {
        return $this->comment($user, $ticket);
    }

    public function close(User $user, Ticket $ticket): bool
    {
        if ($user->isManager()) {
            return true;
        }

        return $ticket->requester_id === $user->id;
    }
}
