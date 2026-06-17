<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Announcement;
use App\Models\Category;
use App\Models\RoleChangeRequest;
use App\Models\Ticket;
use App\Models\TicketApproval;
use Illuminate\Support\Facades\DB;

class HrWorkspaceService
{
    public function build(): array
    {
        $sensitiveCategoryIds = Category::query()
            ->where('is_sensitive', true)
            ->pluck('id');

        $openStatuses = array_map(fn ($status) => $status->value, TicketStatus::open());

        $pendingApprovals = TicketApproval::query()
            ->with(['ticket.requester:id,name', 'ticket.createdFor:id,name', 'ticket.department:id,name', 'ticket.category:id,name'])
            ->where('status', TicketApproval::STATUS_PENDING)
            ->where('approver_role', 'hr')
            ->orderBy('step_order')
            ->orderByDesc('updated_at')
            ->take(4)
            ->get()
            ->map(fn (TicketApproval $approval) => [
                'id' => $approval->id,
                'ticket_id' => $approval->ticket?->id,
                'ticket_title' => $approval->ticket?->title ?? 'Untitled approval',
                'requester_name' => $approval->ticket?->createdFor?->name ?? $approval->ticket?->requester?->name ?? 'Unknown requester',
                'department_name' => $approval->ticket?->department?->name ?? 'No department',
                'step_label' => str($approval->step_key)->replace('_', ' ')->title()->toString(),
                'updated_human' => $approval->updated_at?->diffForHumans() ?? 'recently',
            ]);

        $sensitiveCases = Ticket::query()
            ->with(['requester:id,name', 'createdFor:id,name', 'department:id,name', 'category:id,name'])
            ->open()
            ->whereIn('category_id', $sensitiveCategoryIds)
            ->orderByDesc('updated_at')
            ->take(4)
            ->get()
            ->map(fn (Ticket $ticket) => $this->ticketRow($ticket));

        $peopleTickets = Ticket::query()
            ->with(['requester:id,name', 'createdFor:id,name', 'department:id,name', 'category:id,name'])
            ->whereIn('status', $openStatuses)
            ->where(function ($query) {
                $query->whereIn('template_key', ['missed_punch', 'shift_swap_request'])
                    ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'HR'));
            })
            ->orderByDesc('updated_at')
            ->take(4)
            ->get()
            ->map(fn (Ticket $ticket) => $this->ticketRow($ticket));

        $roleRequests = RoleChangeRequest::query()
            ->with(['target:id,name', 'department:id,name'])
            ->pending()
            ->orderByDesc('created_at')
            ->take(4)
            ->get()
            ->map(fn (RoleChangeRequest $request) => [
                'id' => $request->id,
                'target_name' => $request->target?->name ?? 'Unknown user',
                'requested_role' => $request->requestedRoleLabel(),
                'department_name' => $request->department?->name ?? 'No department',
                'created_human' => $request->created_at?->diffForHumans() ?? 'recently',
            ]);

        $acknowledgementExceptions = Announcement::query()
            ->active()
            ->with(['author:id,name', 'department:id,name'])
            ->withCount([
                'readers as read_count',
                'readers as clarification_count' => fn ($query) => $query
                    ->where('announcement_reads.acknowledgement', Announcement::ACKNOWLEDGEMENT_NEEDS_CLARIFICATION),
            ])
            ->where(function ($query) {
                $query->whereHas('readers', fn ($readerQuery) => $readerQuery
                    ->where('announcement_reads.acknowledgement', Announcement::ACKNOWLEDGEMENT_NEEDS_CLARIFICATION))
                    ->orWhere(function ($urgentQuery) {
                        $urgentQuery->whereIn('priority', ['high', 'urgent'])
                            ->whereDoesntHave('readers');
                    });
            })
            ->ordered()
            ->take(4)
            ->get()
            ->map(fn (Announcement $announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'priority' => $announcement->priority,
                'audience_label' => $announcement->department?->name
                    ?? ($announcement->audience === 'all' ? 'All departments' : 'Managers only'),
                'read_count' => (int) ($announcement->read_count ?? 0),
                'clarification_count' => (int) ($announcement->clarification_count ?? 0),
                'updated_human' => $announcement->updated_at?->diffForHumans() ?? 'recently',
            ]);

        return [
            'summary' => [
                'pending_approvals' => TicketApproval::query()
                    ->where('status', TicketApproval::STATUS_PENDING)
                    ->where('approver_role', 'hr')
                    ->count(),
                'sensitive_cases' => Ticket::query()
                    ->open()
                    ->whereIn('category_id', $sensitiveCategoryIds)
                    ->count(),
                'people_tickets' => Ticket::query()
                    ->whereIn('status', $openStatuses)
                    ->where(function ($query) {
                        $query->whereIn('template_key', ['missed_punch', 'shift_swap_request'])
                            ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'HR'));
                    })
                    ->count(),
                'acknowledgement_exceptions' => DB::table('announcement_reads')
                    ->where('acknowledgement', Announcement::ACKNOWLEDGEMENT_NEEDS_CLARIFICATION)
                    ->count()
                    + Announcement::query()->active()->whereIn('priority', ['high', 'urgent'])->whereDoesntHave('readers')->count(),
            ],
            'pending_approvals' => $pendingApprovals,
            'sensitive_cases' => $sensitiveCases,
            'people_tickets' => $peopleTickets,
            'role_requests' => $roleRequests,
            'acknowledgement_exceptions' => $acknowledgementExceptions,
            'sensitive_category_ids' => $sensitiveCategoryIds->all(),
        ];
    }

    protected function ticketRow(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'title' => $ticket->title,
            'requester_name' => $ticket->createdFor?->name ?? $ticket->requester?->name ?? 'Unknown requester',
            'department_name' => $ticket->department?->name ?? 'No department',
            'category_name' => $ticket->category?->name ?? 'Uncategorised',
            'status_label' => str($ticket->status?->value ?? $ticket->status)->replace('_', ' ')->title()->toString(),
            'updated_human' => $ticket->updated_at?->diffForHumans() ?? 'recently',
        ];
    }
}
