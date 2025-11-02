<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SLAService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketViewController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Ticket::query()
            ->with(['category', 'assignee', 'requester'])
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', $request->input('status'))
            )
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where('title', 'like', '%' . $request->input('search') . '%')
            );

        if ($user->isManager()) {
            if ($request->boolean('mine')) {
                $query->where('assignee_id', $user->id);
            } else {
                $query->open();
            }
        } else {
            $query->where('requester_id', $user->id);
        }

        $tickets = $query
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        return view('tickets.index', [
            'tickets' => $tickets,
            'filters' => $request->only(['status', 'search', 'mine']),
        ]);
    }

    public function show(Request $request, Ticket $ticket, SLAService $slaService): View
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'requester',
            'assignee',
            'category',
            'attachments.uploader',
            'statusChanges.user',
            'duplicateOf',
            'duplicates',
        ]);

        $commentsQuery = $ticket->comments()->with('author');

        if (! $request->user()->hasRole('manager', 'admin')) {
            $commentsQuery->where('is_private', false);
        }

        $comments = $commentsQuery->get();

        $sla = $slaService->evaluate($ticket);
        $assignableUsers = collect();

        if ($request->user()->hasRole('manager', 'admin')) {
            $assignableUsers = User::query()
                ->whereIn('role', ['manager', 'admin'])
                ->orderBy('name')
                ->get();
        }

        return view('tickets.show', [
            'ticket' => $ticket,
            'comments' => $comments,
            'sla' => $sla,
            'statusOptions' => TicketStatus::cases(),
            'priorityOptions' => TicketPriority::cases(),
            'assignableUsers' => $assignableUsers,
        ]);
    }
}
