<?php

namespace App\Http\Controllers\Public;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\TicketStatusRequest;
use App\Models\Ticket;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Services\SlaAutomationService;
use App\Services\SLAService;
use Illuminate\Http\RedirectResponse;

class TicketStatusController extends Controller
{
    public function update(
        TicketStatusRequest $request,
        Ticket $ticket,
        NotificationService $notifier,
        SLAService $slaService,
        AuditLogger $auditLogger,
        SlaAutomationService $slaAutomation
    ): RedirectResponse {
        $statusValue = trim((string) $request->input('status'));
        $status = TicketStatus::from($statusValue);
        $user = $request->user();

        if (
            in_array($status, [TicketStatus::Closed, TicketStatus::Reopened], true)
            && $ticket->requester_id === $user->id
        ) {
            $this->authorize('close', $ticket);
        } else {
            $this->authorize('update', $ticket);
        }

        if ($user->hasRole('manager', 'ops_manager', 'hr', 'admin')) {
            if ($request->has('priority') && $request->filled('priority')) {
                $ticket->priority = $request->input('priority');
            }

            if ($request->has('assignee_id')) {
                $ticket->assignee_id = $request->integer('assignee_id') ?: null;
            }

            if ($ticket->isDirty(['priority', 'assignee_id'])) {
                $ticket->save();
            }
        }

        $comment = $request->filled('comment') ? trim((string) $request->input('comment')) : null;
        $isPrivate = $request->boolean('is_private') && $user->hasRole('manager', 'ops_manager', 'hr', 'admin');

        if ($user->hasRole('manager', 'ops_manager', 'hr', 'admin')) {
            $duplicateId = $request->input('duplicate_of_id');

            if ($duplicateId) {
                $primary = Ticket::findOrFail((int) $duplicateId);
                $ticket->markDuplicateOf($primary, $user, $comment);
            } else {
                if ($ticket->duplicate_of_id) {
                    $ticket->duplicate_of_id = null;
                }

                $ticket->markStatus($status, $user, $comment);
            }
        } else {
            $ticket->markStatus($status, $user, $comment);
        }

        if ($comment) {
            $ticket->comments()->create([
                'user_id' => $user->id,
                'body' => $comment,
                'is_private' => $isPrivate,
            ]);
        }

        $sla = $slaService->evaluate($ticket);

        $ticket->forceFill([
            'sla_first_response_breached' => $sla['first_response_breached'],
            'sla_resolution_breached' => $sla['resolution_breached'],
        ])->save();

        if ($sla['first_response_breached']) {
            $slaAutomation->handleFirstResponseBreach($ticket->fresh(), $user);
            $ticket = $ticket->fresh();
        }

        if ($sla['resolution_breached']) {
            $slaAutomation->handleResolutionBreach($ticket, $user);
            $ticket = $ticket->fresh();
        }

        $notifier->statusUpdated($ticket, $ticket->status->value);

        $auditLogger->log('ticket.status.updated', $ticket, [
            'ticket_id' => $ticket->id,
            'status' => $ticket->status->value,
            'comment' => $comment,
            'priority' => $ticket->priority->value ?? $ticket->priority,
            'assignee_id' => $ticket->assignee_id,
            'duplicate_of_id' => $ticket->duplicate_of_id,
        ]);

        return back()->with('status', 'Ticket updated.');
    }
}


