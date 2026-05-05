<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketApproval;
use App\Models\TicketComment;
use App\Models\TicketStatusChange;
use App\Models\User;
use App\Services\SLAService;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
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

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->boolean('unassigned')) {
            $query->whereNull('assignee_id');
        }

        if ($request->boolean('overdue') || $request->boolean('breached')) {
            $query->where(function ($q) {
                $q->where('sla_first_response_breached', true)
                    ->orWhere('sla_resolution_breached', true);
            });
        }

        if ($request->filled('from_date')) {
            try {
                $from = Carbon::parse((string) $request->input('from_date'))->startOfDay();
                $query->where('updated_at', '>=', $from);
            } catch (\Throwable $e) {
                // Ignore invalid filter input and continue with the rest of the filters.
            }
        }

        if ($request->filled('to_date')) {
            try {
                $to = Carbon::parse((string) $request->input('to_date'))->endOfDay();
                $query->where('updated_at', '<=', $to);
            } catch (\Throwable $e) {
                // Ignore invalid filter input and continue with the rest of the filters.
            }
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

        $categoryFilterOptions = Category::query()
            ->visibleTo($user)
            ->orderBy('name')
            ->pluck('name', 'id');

        $approvalQueue = collect();

        if ($user->hasRole('manager', 'ops_manager', 'hr', 'admin')) {
            $approvalQueue = $this->buildApprovalQueue($user);
        }

        return view('tickets.index', [
            'tickets' => $tickets,
            'filters' => $request->only(['status', 'search', 'mine', 'department_id', 'overdue', 'breached', 'from_date', 'to_date', 'category_id', 'unassigned']),
            'departmentFilterOptions' => $departmentFilterOptions,
            'categoryFilterOptions' => $categoryFilterOptions,
            'approvalQueue' => $approvalQueue,
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
            'approvals.approver',
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

        $templateMeta = $ticket->template_key
            ? collect(config('ticket_templates', []))->get($ticket->template_key)
            : null;
        $approvalSummary = $ticket->approvals->map(function ($approval) {
            return [
                'id' => $approval->id,
                'step_order' => $approval->step_order,
                'step_key' => $approval->step_key,
                'approver_role' => $approval->approver_role,
                'status' => $approval->status,
                'status_label' => $approval->publicStatusLabel(),
                'public_note' => $approval->public_note,
                'internal_note' => $approval->internal_note,
                'approver_name' => $approval->approver?->name,
                'decided_at' => $approval->decided_at,
            ];
        });
        $approvalContext = $this->buildApprovalContext($ticket->approvals);
        $visibleAttachments = $ticket->attachments
            ->filter(fn ($attachment) => $attachment->visibleTo($request->user()))
            ->values();
        $lifecycle = $this->buildLifecycleSummary($ticket, $comments, $sla);
        $timelineEntries = $this->buildTicketTimeline($ticket, $comments, $visibleAttachments);

        return view('tickets.show', [
            'ticket' => $ticket,
            'comments' => $comments,
            'sla' => $sla,
            'lifecycle' => $lifecycle,
            'timelineEntries' => $timelineEntries,
            'templateMeta' => $templateMeta,
            'approvalSummary' => $approvalSummary,
            'approvalContext' => $approvalContext,
            'visibleAttachments' => $visibleAttachments,
            'statusOptions' => TicketStatus::cases(),
            'priorityOptions' => TicketPriority::cases(),
            'assignableUsers' => $assignableUsers,
        ]);
    }

    protected function buildApprovalQueue(User $user): Collection
    {
        $query = Ticket::query()
            ->with(['requester:id,name', 'department:id,name', 'approvals' => fn ($q) => $q->where('status', \App\Models\TicketApproval::STATUS_PENDING)->orderBy('step_order')])
            ->whereHas('approvals', function ($approvalQuery) use ($user) {
                $approvalQuery->where('status', \App\Models\TicketApproval::STATUS_PENDING);

                if ($user->hasRole('hr', 'admin')) {
                    return;
                }

                $managedDepartmentIds = $user->managedDepartments()->pluck('departments.id');

                $approvalQuery->where('approver_role', 'manager');
                $approvalQuery->whereHas('ticket', fn ($ticketQuery) => $ticketQuery->whereIn('department_id', $managedDepartmentIds));
            })
            ->orderByDesc('updated_at')
            ->take(4)
            ->get();

        return $query->map(function (Ticket $ticket) {
            $approval = $ticket->approvals->first();

            return [
                'ticket_id' => $ticket->id,
                'title' => $ticket->title,
                'requester_name' => $ticket->requester?->name,
                'department_name' => $ticket->department?->name,
                'step_key' => $approval?->step_key,
                'approver_role' => $approval?->approver_role,
                'status_label' => $approval?->publicStatusLabel(),
            ];
        });
    }

    protected function buildApprovalContext(Collection $approvals): array
    {
        if ($approvals->isEmpty()) {
            return [
                'current' => collect(),
                'history' => collect(),
                'requester_status' => null,
                'requester_note' => null,
            ];
        }

        $current = $approvals
            ->filter(fn (TicketApproval $approval) => in_array($approval->status, [TicketApproval::STATUS_PENDING, TicketApproval::STATUS_QUEUED], true))
            ->sortBy('step_order')
            ->values();

        $history = $approvals
            ->filter(fn (TicketApproval $approval) => ! in_array($approval->status, [TicketApproval::STATUS_PENDING, TicketApproval::STATUS_QUEUED], true))
            ->sortByDesc('step_order')
            ->values();

        $active = $current->firstWhere('status', TicketApproval::STATUS_PENDING);
        $queued = $current->firstWhere('status', TicketApproval::STATUS_QUEUED);

        $requesterStatus = null;
        $requesterNote = null;

        if ($active?->approver_role === 'manager') {
            $requesterStatus = 'Awaiting manager approval';
            $requesterNote = $queued
                ? 'Manager approval is the current step. HR finalization will only start after the manager signs off.'
                : 'A manager decision is needed before this request can move forward.';
        } elseif ($active?->approver_role === 'hr') {
            $requesterStatus = 'Awaiting weekday HR review';
            $requesterNote = 'HR review is pending. Night HR coverage can usually request more information or handle basic attendance fixes, but final approval may wait for the weekday HR team.';
        } elseif ($queued?->approver_role === 'hr') {
            $requesterStatus = 'Queued for weekday HR review';
            $requesterNote = 'The next approval step is HR finalization. That review will start only after the current approval is completed.';
        } elseif ($history->isNotEmpty() && $history->every(fn (TicketApproval $approval) => $approval->status === TicketApproval::STATUS_APPROVED)) {
            $requesterStatus = 'All approval steps complete';
            $requesterNote = 'The approval chain is complete. The ticket should now move into completion or confirmation.';
        } elseif ($history->contains(fn (TicketApproval $approval) => $approval->status === TicketApproval::STATUS_NEEDS_INFO)) {
            $requesterStatus = 'Approval waiting on more information from you';
            $requesterNote = 'One of the approval steps requested more information before the workflow can continue.';
        }

        return [
            'current' => $current,
            'history' => $history,
            'requester_status' => $requesterStatus,
            'requester_note' => $requesterNote,
        ];
    }

    /**
     * @param  Collection<int, TicketComment>  $comments
     * @param  array<string, mixed>  $sla
     * @return array<string, string|bool|null>
     */
    protected function buildLifecycleSummary(Ticket $ticket, Collection $comments, array $sla): array
    {
        $status = $ticket->status instanceof TicketStatus ? $ticket->status : TicketStatus::from((string) $ticket->status);
        $ownerLabel = $ticket->assignee?->name;
        $ownerDetail = 'A named owner has not been assigned yet.';
        $nextStep = 'The support team should review this ticket and decide the next action.';
        $headline = 'Your issue has been logged and is waiting for first review.';
        $requesterActionLabel = null;
        $requesterActionDetail = null;

        switch ($status) {
            case TicketStatus::New:
                $headline = 'Your issue has been logged and is waiting for first review.';
                $nextStep = $ticket->assignee
                    ? 'The assigned owner should review the report, confirm priority, and start triage.'
                    : 'A manager or support owner should review this report and decide who will pick it up.';
                if ($ticket->assignee) {
                    $ownerDetail = 'This ticket already has an owner and should move into triage shortly.';
                }
                break;

            case TicketStatus::Triaged:
                $headline = 'The issue has been reviewed and routed to the right queue.';
                $nextStep = $ticket->assignee
                    ? 'The assigned owner should begin work or ask for any missing detail.'
                    : 'The support team should assign an owner and begin work.';
                $ownerDetail = $ticket->assignee
                    ? 'The ticket has been triaged and is now with the assigned owner.'
                    : 'The ticket has been triaged, but ownership still needs to be set.';
                break;

            case TicketStatus::InProgress:
                $headline = 'Work is actively underway on this issue.';
                $nextStep = 'Expect more updates here as work progresses or if the team needs more information.';
                $ownerDetail = $ticket->assignee
                    ? 'The assigned owner is actively working the issue.'
                    : 'The issue is in progress, but no named owner is shown yet.';
                break;

            case TicketStatus::WaitingEmployee:
                $headline = 'The support team is waiting for something from you before work can continue.';
                $ownerLabel = 'Waiting on you';
                $ownerDetail = 'Reply with the missing detail, confirm the fix, or provide access so the ticket can move again.';
                $nextStep = 'Add the missing detail or confirm the next step so work can continue.';
                $requesterActionLabel = 'Action needed from you';
                $requesterActionDetail = 'Open the updates below and reply with the detail or confirmation the team is waiting for.';
                break;

            case TicketStatus::Resolved:
                $headline = 'A fix has been marked as complete and is waiting for your confirmation.';
                $ownerLabel = $ticket->assignee?->name ?? 'Awaiting requester confirmation';
                $ownerDetail = 'If the issue is fixed, confirm and close the ticket. If not, reopen it so the team can continue.';
                $nextStep = 'Test the fix in your area, then either confirm closure or reopen the ticket.';
                $requesterActionLabel = 'Review the fix';
                $requesterActionDetail = 'Use the requester actions on this page to close the ticket or reopen it if the problem remains.';
                break;

            case TicketStatus::Closed:
                $headline = 'This ticket has been completed and closed.';
                $ownerLabel = $ticket->assignee?->name ?? 'Completed';
                $ownerDetail = 'No further work is expected unless the issue returns.';
                $nextStep = 'Reopen the ticket if the same problem comes back or the fix did not hold.';
                break;

            case TicketStatus::Reopened:
                $headline = 'This ticket has been reopened and returned to the active queue.';
                $nextStep = $ticket->assignee
                    ? 'The assigned owner should review what failed and continue work.'
                    : 'The support team should pick this back up and assign ownership again.';
                $ownerDetail = $ticket->assignee
                    ? 'Ownership has been retained after reopening.'
                    : 'The issue is active again and waiting for a new owner.';
                break;

            case TicketStatus::Cancelled:
                $headline = $ticket->duplicateOf
                    ? 'This ticket was closed as a duplicate of another report.'
                    : 'This ticket was cancelled and is no longer being worked.';
                $ownerLabel = $ticket->duplicateOf ? 'Merged into another ticket' : 'Cancelled';
                $ownerDetail = $ticket->duplicateOf
                    ? 'Follow the linked primary ticket for future updates.'
                    : 'If this was cancelled in error, create a new report or contact support.';
                $nextStep = $ticket->duplicateOf
                    ? 'Check the linked primary ticket for the latest progress.'
                    : 'No further work is planned on this ticket.';
                break;
        }

        if ($ticket->assignee && $status !== TicketStatus::WaitingEmployee) {
            $ownerLabel = $ticket->assignee->name;
        } elseif (! $ownerLabel) {
            $ownerLabel = $ticket->department?->name
                ? $ticket->department->name . ' support queue'
                : 'Support queue';
        }

        $latestVisibleUpdateAt = $comments
            ->where('is_private', false)
            ->sortByDesc('created_at')
            ->first()?->created_at;

        if (! $latestVisibleUpdateAt && $ticket->statusChanges->isNotEmpty()) {
            $latestVisibleUpdateAt = $ticket->statusChanges->last()?->created_at;
        }

        $serviceNote = 'This ticket is currently within its service targets.';

        if (
            ($sla['first_response_breached'] ?? false)
            || ($sla['resolution_breached'] ?? false)
            || ($ticket->sla_first_response_breached ?? false)
            || ($ticket->sla_resolution_breached ?? false)
        ) {
            $serviceNote = 'This ticket has missed a service target and should be treated as urgent.';
        } elseif (($sla['first_response_minutes'] ?? null) === null) {
            $serviceNote = 'The first response is still pending.';
        } elseif ($status === TicketStatus::Resolved) {
            $serviceNote = 'Service targets are now focused on closure confirmation rather than active work.';
        }

        return [
            'headline' => $headline,
            'statusLabel' => ucfirst(str_replace('_', ' ', $status->value)),
            'ownerLabel' => $ownerLabel,
            'ownerDetail' => $ownerDetail,
            'nextStep' => $nextStep,
            'serviceNote' => $serviceNote,
            'latestVisibleUpdate' => $latestVisibleUpdateAt?->diffForHumans() ?? 'No visible updates yet.',
            'requesterActionLabel' => $requesterActionLabel,
            'requesterActionDetail' => $requesterActionDetail,
            'isRequesterActionRequired' => $status === TicketStatus::WaitingEmployee || $status === TicketStatus::Resolved,
        ];
    }

    protected function statusTimelineHeadline(TicketStatusChange $change): string
    {
        $toStatus = $change->to_status instanceof TicketStatus
            ? $change->to_status
            : TicketStatus::from((string) $change->to_status);

        return match ($toStatus) {
            TicketStatus::New => 'Issue logged',
            TicketStatus::Triaged => 'Reviewed and routed',
            TicketStatus::InProgress => 'Work started',
            TicketStatus::WaitingEmployee => 'Waiting for requester response',
            TicketStatus::Resolved => 'Fix marked complete',
            TicketStatus::Closed => 'Ticket closed',
            TicketStatus::Reopened => 'Ticket reopened',
            TicketStatus::Cancelled => 'Ticket cancelled',
        };
    }

    protected function statusTimelineDetail(TicketStatusChange $change): string
    {
        $toStatus = $change->to_status instanceof TicketStatus
            ? $change->to_status
            : TicketStatus::from((string) $change->to_status);

        return match ($toStatus) {
            TicketStatus::New => 'The report entered the queue and is waiting for first review.',
            TicketStatus::Triaged => 'The issue was reviewed and routed to the right team or queue.',
            TicketStatus::InProgress => 'Someone is actively working on the issue.',
            TicketStatus::WaitingEmployee => 'The team needs more detail, confirmation, or access from the requester.',
            TicketStatus::Resolved => 'A fix was applied and is waiting for requester confirmation.',
            TicketStatus::Closed => 'The issue was confirmed complete and no more work is planned.',
            TicketStatus::Reopened => 'The issue came back or the fix did not hold, so work resumed.',
            TicketStatus::Cancelled => 'The ticket was cancelled or merged into another report.',
        };
    }

    /**
     * @param  Collection<int, TicketComment>  $comments
     * @param  Collection<int, \App\Models\TicketAttachment>  $attachments
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildTicketTimeline(Ticket $ticket, Collection $comments, Collection $attachments): Collection
    {
        $entries = collect([
            [
                'type' => 'Opened',
                'headline' => 'Ticket opened',
                'detail' => 'The request was logged and added to the support queue.',
                'actor' => $ticket->requester?->name,
                'created_at' => $ticket->created_at,
                'tone' => 'blue',
            ],
        ]);

        $ticket->statusChanges->each(function (TicketStatusChange $change) use ($entries): void {
            $entries->push([
                'type' => 'Status',
                'headline' => $this->statusTimelineHeadline($change),
                'detail' => $this->statusTimelineDetail($change),
                'note' => $change->reason,
                'actor' => $change->user?->name,
                'created_at' => $change->created_at,
                'tone' => $this->statusTimelineTone($change),
            ]);
        });

        $comments->each(function (TicketComment $comment) use ($entries): void {
            $entries->push([
                'type' => $comment->is_private ? 'Private note' : 'Update',
                'headline' => $comment->is_private ? 'Internal note added' : 'Update posted',
                'detail' => $comment->body,
                'actor' => $comment->author?->name,
                'created_at' => $comment->created_at,
                'tone' => $comment->is_private ? 'amber' : 'slate',
            ]);
        });

        $attachments->each(function ($attachment) use ($entries): void {
            $entries->push([
                'type' => 'Evidence',
                'headline' => 'Attachment added',
                'detail' => $attachment->label ?: $attachment->original_name,
                'note' => $attachment->kindLabel(),
                'actor' => $attachment->uploader?->name,
                'created_at' => $attachment->created_at,
                'tone' => 'slate',
            ]);
        });

        return $entries
            ->filter(fn (array $entry) => $entry['created_at'])
            ->sortBy('created_at')
            ->values();
    }

    protected function statusTimelineTone(TicketStatusChange $change): string
    {
        $toStatus = $change->to_status instanceof TicketStatus
            ? $change->to_status
            : TicketStatus::from((string) $change->to_status);

        return match ($toStatus) {
            TicketStatus::WaitingEmployee, TicketStatus::Reopened => 'amber',
            TicketStatus::Resolved, TicketStatus::Closed => 'green',
            TicketStatus::Cancelled => 'slate',
            default => 'blue',
        };
    }
}
