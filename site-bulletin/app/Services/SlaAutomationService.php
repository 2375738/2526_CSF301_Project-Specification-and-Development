<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Department;
use App\Models\Message;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

class SlaAutomationService
{
    public function __construct(protected AuditLogger $auditLogger)
    {
    }

    public function handleFirstResponseBreach(Ticket $ticket, User $actor): void
    {
        if ($ticket->notified_first_response_breach) {
            return;
        }

        $this->notifyStakeholders($ticket, $actor, 'first response');

        $ticket->forceFill(['notified_first_response_breach' => true])->saveQuietly();

        $this->auditLogger->log('sla.breach.first_response', $ticket, [
            'ticket_id' => $ticket->id,
            'department_id' => $ticket->department_id,
        ], $actor);
    }

    public function handleResolutionBreach(Ticket $ticket, User $actor): void
    {
        if ($ticket->notified_resolution_breach) {
            return;
        }

        $this->notifyStakeholders($ticket, $actor, 'resolution');

        $ticket->forceFill(['notified_resolution_breach' => true])->saveQuietly();

        $this->auditLogger->log('sla.breach.resolution', $ticket, [
            'ticket_id' => $ticket->id,
            'department_id' => $ticket->department_id,
        ], $actor);
    }

    protected function notifyStakeholders(Ticket $ticket, User $actor, string $type): void
    {
        $participants = $this->stakeholders($ticket);

        if ($participants->isEmpty()) {
            return;
        }

        $conversation = Conversation::create([
            'subject' => sprintf('SLA %s breach · Ticket #%d', ucfirst($type), $ticket->id),
            'type' => 'announcement',
            'creator_id' => $actor->id,
            'department_id' => $ticket->department_id,
            'is_locked' => false,
        ]);

        $sync = $participants
            ->merge([$actor])
            ->unique('id')
            ->mapWithKeys(fn (User $user) => [
                $user->id => ['role' => $user->id === $actor->id ? 'owner' : 'member', 'last_read_at' => null],
            ])
            ->toArray();

        $conversation->participants()->sync($sync);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $actor->id,
            'body' => sprintf(
                'Ticket #%d (%s) breached %s SLA. Please review the queue and take action.',
                $ticket->id,
                $ticket->title,
                $type
            ),
        ]);
    }

    protected function stakeholders(Ticket $ticket): Collection
    {
        $participants = collect();

        if ($ticket->department_id) {
            /** @var Department|null $department */
            $department = Department::find($ticket->department_id);

            if ($department) {
                $participants = $department->managers()->get();
            }
        }

        if ($participants->isEmpty()) {
            $participants = User::query()
                ->whereIn('role', ['hr', 'admin'])
                ->get();
        }

        return $participants;
    }
}
