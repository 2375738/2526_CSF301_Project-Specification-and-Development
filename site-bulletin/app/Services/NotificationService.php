<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function ticketCreated(Ticket $ticket): void
    {
        $this->log('ticket.created', [
            'ticket_id' => $ticket->id,
            'requester_id' => $ticket->requester_id,
            'assignee_id' => $ticket->assignee_id,
        ]);
    }

    public function ticketAssigned(Ticket $ticket, User $assignee): void
    {
        $this->log('ticket.assigned', [
            'ticket_id' => $ticket->id,
            'assignee_id' => $assignee->id,
        ]);
    }

    public function statusUpdated(Ticket $ticket, string $status): void
    {
        $this->log('ticket.status', [
            'ticket_id' => $ticket->id,
            'status' => $status,
        ]);
    }

    public function commentAdded(Ticket $ticket, User $author, bool $isPrivate): void
    {
        $this->log('ticket.comment', [
            'ticket_id' => $ticket->id,
            'author_id' => $author->id,
            'is_private' => $isPrivate,
        ]);
    }

    protected function log(string $event, array $context = []): void
    {
        Log::info("[notifications] {$event}", $context);
    }
}
