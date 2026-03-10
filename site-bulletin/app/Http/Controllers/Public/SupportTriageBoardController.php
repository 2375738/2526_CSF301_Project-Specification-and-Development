<?php

namespace App\Http\Controllers\Public;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportTriageBoardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $scopeDepartmentIds = $user->hasRole('hr', 'admin')
            ? null
            : $user->managedDepartments()->pluck('departments.id');

        $baseQuery = Ticket::query()
            ->with(['requester:id,name', 'assignee:id,name', 'category:id,name', 'department:id,name']);

        if ($scopeDepartmentIds !== null) {
            if ($scopeDepartmentIds->isEmpty()) {
                $baseQuery->whereRaw('1 = 0');
            } else {
                $baseQuery->whereIn('department_id', $scopeDepartmentIds);
            }
        }

        $unassignedNew = (clone $baseQuery)
            ->whereIn('status', [TicketStatus::New->value, TicketStatus::Triaged->value])
            ->whereNull('assignee_id')
            ->orderByDesc('updated_at')
            ->take(6)
            ->get();

        $breachedQueue = (clone $baseQuery)
            ->whereIn('status', array_map(fn ($status) => $status->value, TicketStatus::open()))
            ->where(function ($query) {
                $query->where('sla_first_response_breached', true)
                    ->orWhere('sla_resolution_breached', true);
            })
            ->orderByDesc('updated_at')
            ->take(6)
            ->get();

        $waitingOnEmployee = (clone $baseQuery)
            ->where('status', TicketStatus::WaitingEmployee)
            ->orderByDesc('updated_at')
            ->take(6)
            ->get();

        $recentlyUpdated = (clone $baseQuery)
            ->whereIn('status', array_map(fn ($status) => $status->value, TicketStatus::open()))
            ->orderByDesc('updated_at')
            ->take(6)
            ->get();

        $ownershipSummary = (clone $baseQuery)
            ->selectRaw('assignee_id, COUNT(*) as total')
            ->whereIn('status', array_map(fn ($status) => $status->value, TicketStatus::open()))
            ->groupBy('assignee_id')
            ->with('assignee:id,name')
            ->orderByDesc('total')
            ->take(8)
            ->get()
            ->map(fn (Ticket $ticket) => [
                'assignee_id' => $ticket->assignee_id,
                'assignee_name' => $ticket->assignee?->name ?? 'Unassigned',
                'total' => (int) ($ticket->total ?? 0),
            ]);

        $categorySummary = (clone $baseQuery)
            ->selectRaw('category_id, COUNT(*) as total')
            ->whereIn('status', array_map(fn ($status) => $status->value, TicketStatus::open()))
            ->groupBy('category_id')
            ->with('category:id,name')
            ->orderByDesc('total')
            ->take(8)
            ->get()
            ->map(fn (Ticket $ticket) => [
                'category_id' => $ticket->category_id,
                'category_name' => $ticket->category?->name ?? 'Uncategorised',
                'total' => (int) ($ticket->total ?? 0),
            ]);

        $departmentSummary = (clone $baseQuery)
            ->selectRaw('department_id, COUNT(*) as total')
            ->whereIn('status', array_map(fn ($status) => $status->value, TicketStatus::open()))
            ->groupBy('department_id')
            ->with('department:id,name')
            ->orderByDesc('total')
            ->take(8)
            ->get()
            ->map(fn (Ticket $ticket) => [
                'department_id' => $ticket->department_id,
                'department_name' => $ticket->department?->name ?? 'No department',
                'total' => (int) ($ticket->total ?? 0),
            ]);

        return view('tickets.triage', [
            'unassignedNew' => $unassignedNew,
            'breachedQueue' => $breachedQueue,
            'waitingOnEmployee' => $waitingOnEmployee,
            'recentlyUpdated' => $recentlyUpdated,
            'ownershipSummary' => $ownershipSummary,
            'categorySummary' => $categorySummary,
            'departmentSummary' => $departmentSummary,
            'scopeDepartmentIds' => $scopeDepartmentIds,
            'categoryOptions' => Category::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
