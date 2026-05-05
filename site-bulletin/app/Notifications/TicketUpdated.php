<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketUpdated extends Notification
{
    use Queueable;

    public $ticket;
    public $action;
    public $performer;

    /**
     * Create a new notification instance.
     */
    public function __construct(Ticket $ticket, string $action, $performer)
    {
        $this->ticket = $ticket;
        $this->action = $action;
        $this->performer = $performer;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'title' => 'Ticket #' . $this->ticket->id . ': ' . $this->ticket->title,
            'action' => $this->action, // e.g., "updated", "resolved", "commented"
            'action_label' => match ($this->action) {
                'resolved' => 'Review fix',
                'commented' => 'Read update',
                'assigned' => 'Open ticket',
                default => 'View ticket',
            },
            'performer_name' => $this->performer->name,
            'url' => route('tickets.show', $this->ticket),
            'type' => 'ticket',
            'message' => "Ticket #{$this->ticket->id} was {$this->action} by {$this->performer->name}",
        ];
    }
}
