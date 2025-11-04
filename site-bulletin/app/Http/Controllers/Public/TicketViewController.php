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
            ->with(['category', 'assignee', 'requester', 'createdFor', 'department'])
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', $request->input('status'))
            )
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where('title', 'like', '%' . $request->input('search') . '%')
            );

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }

        if ($request->boolean('overdue')) {
            $query->where(function ($q) {
                $q->where('sla_first_response_breached', true)
                    ->orWhere('sla_resolution_breached', true);
            });
        }

        if ($user->isManager() || $user->isHr()) {
            if ($request->boolean('mine')) {
                $query->where('assignee_id', $user->id);
            } else {
                $query->open();
            }
        } else {
            $query->where(function ($q) use ($user) {
                $q->where('requester_id', $user->id)
                    ->orWhere('created_for_id', $user->id);
            });
        }

        $tickets = $query
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        $departmentFilterOptions = collect();

        if ($user->hasRole('hr', 'admin', 'ops_manager')) {
            $departmentFilterOptions = \App\Models\Department::orderBy('name')->pluck('name', 'id');
        } elseif ($user->isManager()) {
            $departmentFilterOptions = $user->managedDepartments()->orderBy('departments.name')->pluck('departments.name', 'departments.id');
        } elseif ($user->primaryDepartment) {
            $departmentFilterOptions = collect([$user->primaryDepartment])->filter()->mapWithKeys(fn ($dept) => [$dept->id => $dept->name]);
        }

        return view('tickets.index', [
            'tickets' => $tickets,
            'filters' => $request->only(['status', 'search', 'mine', 'department_id', 'overdue']),
            'departmentFilterOptions' => $departmentFilterOptions,
        ]);
    }

    public function show(Request $request, Ticket $ticket, SLAService $slaService): View
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'requester',
            'assignee',
            'category',
            'createdFor',
            'department',
            'attachments.uploader',
            'statusChanges.user',
            'duplicateOf',
            'duplicates',
        ]);

        $commentsQuery = $ticket->comments()->with('author');

        if (! $request->user()->hasRole('manager', 'ops_manager', 'hr', 'admin')) {
            $commentsQuery->where('is_private', false);
        }

        $comments = $commentsQuery->get();

        $sla = $slaService->evaluate($ticket);
        $assignableUsers = collect();

        if ($request->user()->hasRole('manager', 'ops_manager', 'hr', 'admin')) {
            $assignableUsers = User::query()
                ->whereIn('role', ['manager', 'ops_manager', 'hr', 'admin'])
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
