<?php

namespace App\Http\Controllers\Public;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\TicketCommentRequest;
use App\Models\Ticket;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;

class TicketCommentController extends Controller
{
    public function store(
        TicketCommentRequest $request,
        Ticket $ticket,
        NotificationService $notifier
    ): RedirectResponse {
        $this->authorize('comment', $ticket);

        $user = $request->user();
        $isPrivate = $request->boolean('is_private') && $user->hasRole('manager', 'admin');

        $comment = $ticket->comments()->create([
            'user_id' => $user->id,
            'body' => $request->input('body'),
            'is_private' => $isPrivate,
        ]);

        if (
            $ticket->status === TicketStatus::WaitingEmployee
            && $ticket->requester_id === $user->id
            && ! $isPrivate
        ) {
            $ticket->markStatus(TicketStatus::InProgress, $user, 'Requester provided update');
        }

        $notifier->commentAdded($ticket, $user, $isPrivate);

        return back()->with('status', 'Comment added.');
    }
}
