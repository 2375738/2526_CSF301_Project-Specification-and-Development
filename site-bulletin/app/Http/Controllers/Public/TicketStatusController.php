<?php

namespace App\Http\Controllers\Public;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\TicketStatusRequest;
use App\Models\Ticket;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;

class TicketStatusController extends Controller
{
    public function update(
        TicketStatusRequest $request,
        Ticket $ticket,
        NotificationService $notifier
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

        if ($user->hasRole('manager', 'admin')) {
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
        $isPrivate = $request->boolean('is_private') && $user->hasRole('manager', 'admin');

        if ($user->hasRole('manager', 'admin')) {
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

        $notifier->statusUpdated($ticket, $ticket->status->value);

        return back()->with('status', 'Ticket updated.');
    }
}


