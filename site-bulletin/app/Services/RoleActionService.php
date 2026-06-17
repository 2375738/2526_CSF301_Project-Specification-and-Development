<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Announcement;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\RoleChangeRequest;
use App\Models\Ticket;
use App\Models\TicketApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RoleActionService
{
    public function __construct(
        protected PerformanceService $performanceService,
        protected RoleScopeService $roleScope
    ) {
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function forUser(User $user, Collection $snapshots = null): Collection
    {
        $actions = match (true) {
            $user->isEmployee() => $this->employeeActions($user, $snapshots ?? collect()),
            $user->hasRole('hr') => $this->hrActions($user),
            $user->hasRole('ops_manager') => $this->opsManagerActions($user),
            $user->hasRole('admin') => $this->adminActions($user),
            $user->hasRole('manager') => $this->managerActions($user),
            default => collect(),
        };

        return $actions
            ->sortBy(fn (array $action) => $action['priority'] ?? 50)
            ->take(6)
            ->values();
    }

    protected function employeeActions(User $user, Collection $snapshots): Collection
    {
        $actions = collect();

        $waitingTicket = Ticket::query()
            ->where(function (Builder $query) use ($user) {
                $query->where('requester_id', $user->id)
                    ->orWhere('created_for_id', $user->id);
            })
            ->where('status', TicketStatus::WaitingEmployee)
            ->orderByDesc('updated_at')
            ->first();

        if ($waitingTicket) {
            $actions->push($this->action(
                'Reply to a waiting ticket',
                $waitingTicket->title,
                route('tickets.show', $waitingTicket),
                'Ticket',
                'amber',
                10
            ));
        }

        $resolvedTicket = Ticket::query()
            ->where(function (Builder $query) use ($user) {
                $query->where('requester_id', $user->id)
                    ->orWhere('created_for_id', $user->id);
            })
            ->where('status', TicketStatus::Resolved)
            ->orderByDesc('updated_at')
            ->first();

        if ($resolvedTicket) {
            $actions->push($this->action(
                'Confirm a resolved ticket',
                $resolvedTicket->title,
                route('tickets.show', $resolvedTicket),
                'Ticket',
                'emerald',
                20
            ));
        }

        $urgentAnnouncement = Announcement::query()
            ->active()
            ->visibleTo($user)
            ->whereIn('priority', ['high', 'urgent'])
            ->whereDoesntHave('readers', fn (Builder $query) => $query->where('users.id', $user->id))
            ->ordered()
            ->first();

        if ($urgentAnnouncement) {
            $actions->push($this->action(
                'Read urgent update',
                $urgentAnnouncement->title,
                route('announcements.show', $urgentAnnouncement),
                'Announcement',
                'rose',
                30
            ));
        }

        $unreadConversation = $this->latestUnreadConversation($user);

        if ($unreadConversation) {
            $actions->push($this->action(
                'Read unread conversation',
                $unreadConversation->subject ?: 'New message thread',
                route('messages.show', $unreadConversation),
                'Message',
                'blue',
                40
            ));
        }

        if ($this->performanceService->riskFlag($snapshots)) {
            $actions->push($this->action(
                'Review coaching feedback',
                'Your recent performance trend needs attention.',
                route('my-work.index'),
                'My Work',
                'indigo',
                50
            ));
        }

        return $actions;
    }

    protected function managerActions(User $user): Collection
    {
        $actions = collect();
        $departmentIds = $this->roleScope->manageableDepartmentIds($user) ?? collect();

        $breachedCount = $this->scopedOpenTickets($user)
            ->where(function (Builder $query) {
                $query->where('sla_first_response_breached', true)
                    ->orWhere('sla_resolution_breached', true);
            })
            ->count();

        if ($breachedCount > 0) {
            $actions->push($this->action(
                'Clear breached tickets',
                "{$breachedCount} open ticket" . ($breachedCount === 1 ? '' : 's') . ' outside SLA.',
                route('tickets.index', ['breached' => 1]),
                'Tickets',
                'orange',
                10
            ));
        }

        $waitingCount = $this->scopedOpenTickets($user)
            ->where('status', TicketStatus::WaitingEmployee)
            ->count();

        if ($waitingCount > 0) {
            $actions->push($this->action(
                'Chase employee follow-up',
                "{$waitingCount} ticket" . ($waitingCount === 1 ? '' : 's') . ' waiting on employee input.',
                route('tickets.index', ['status' => TicketStatus::WaitingEmployee->value]),
                'Tickets',
                'amber',
                20
            ));
        }

        $pendingApprovals = TicketApproval::query()
            ->where('status', TicketApproval::STATUS_PENDING)
            ->where('approver_role', 'manager')
            ->whereHas('ticket', fn (Builder $query) => $query->whereIn('department_id', $departmentIds))
            ->count();

        if ($pendingApprovals > 0) {
            $actions->push($this->action(
                'Review pending approvals',
                "{$pendingApprovals} approval" . ($pendingApprovals === 1 ? '' : 's') . ' waiting for manager decision.',
                route('tickets.approvals.index', ['status' => TicketApproval::STATUS_PENDING]),
                'Approvals',
                'emerald',
                30
            ));
        }

        $roleRequests = RoleChangeRequest::query()
            ->pending()
            ->whereIn('department_id', $departmentIds)
            ->count();

        if ($roleRequests > 0) {
            $actions->push($this->action(
                'Review role requests',
                "{$roleRequests} role request" . ($roleRequests === 1 ? '' : 's') . ' in managed departments.',
                route('role-requests.index'),
                'Governance',
                'slate',
                40
            ));
        }

        $unreadConversation = $this->latestUnreadConversation($user);

        if ($unreadConversation) {
            $actions->push($this->action(
                'Answer unread conversation',
                $unreadConversation->subject ?: 'New message thread',
                route('messages.show', $unreadConversation),
                'Message',
                'blue',
                50
            ));
        }

        return $actions;
    }

    protected function opsManagerActions(User $user): Collection
    {
        $actions = collect();

        $triageCount = $this->scopedOpenTickets($user)
            ->whereNull('assignee_id')
            ->count();

        if ($triageCount > 0) {
            $actions->push($this->action(
                'Balance unassigned work',
                "{$triageCount} open ticket" . ($triageCount === 1 ? '' : 's') . ' without an owner.',
                route('tickets.triage'),
                'Triage',
                'blue',
                10
            ));
        }

        $breachedCount = $this->scopedOpenTickets($user)
            ->where(function (Builder $query) {
                $query->where('sla_first_response_breached', true)
                    ->orWhere('sla_resolution_breached', true);
            })
            ->count();

        if ($breachedCount > 0) {
            $actions->push($this->action(
                'Reduce site SLA pressure',
                "{$breachedCount} open ticket" . ($breachedCount === 1 ? '' : 's') . ' outside SLA across the site.',
                route('tickets.index', ['breached' => 1]),
                'Tickets',
                'orange',
                20
            ));
        }

        $agingCount = $this->scopedOpenTickets($user)
            ->where('updated_at', '<=', now()->subDays(3))
            ->count();

        if ($agingCount > 0) {
            $actions->push($this->action(
                'Review aging tickets',
                "{$agingCount} open ticket" . ($agingCount === 1 ? '' : 's') . ' have not moved in 3 days.',
                route('tickets.index'),
                'Tickets',
                'amber',
                30
            ));
        }

        return $actions->merge($this->managerActions($user))->take(6);
    }

    protected function hrActions(User $user): Collection
    {
        $actions = collect();

        $pendingHrApprovals = TicketApproval::query()
            ->where('status', TicketApproval::STATUS_PENDING)
            ->where('approver_role', 'hr')
            ->count();

        if ($pendingHrApprovals > 0) {
            $actions->push($this->action(
                'Complete HR approvals',
                "{$pendingHrApprovals} HR approval" . ($pendingHrApprovals === 1 ? '' : 's') . ' waiting.',
                route('tickets.approvals.index', ['approver_role' => 'hr', 'status' => TicketApproval::STATUS_PENDING]),
                'Approvals',
                'emerald',
                10
            ));
        }

        $sensitiveCategoryIds = Category::query()
            ->where('is_sensitive', true)
            ->pluck('id');

        $sensitiveTickets = Ticket::query()
            ->open()
            ->whereIn('category_id', $sensitiveCategoryIds)
            ->count();

        if ($sensitiveTickets > 0) {
            $actions->push($this->action(
                'Review sensitive people cases',
                "{$sensitiveTickets} sensitive open ticket" . ($sensitiveTickets === 1 ? '' : 's') . '.',
                route('tickets.index'),
                'Tickets',
                'rose',
                20
            ));
        }

        $roleRequests = RoleChangeRequest::query()->pending()->count();

        if ($roleRequests > 0) {
            $actions->push($this->action(
                'Decide role requests',
                "{$roleRequests} role request" . ($roleRequests === 1 ? '' : 's') . ' awaiting governance review.',
                route('role-requests.index'),
                'Governance',
                'slate',
                30
            ));
        }

        $policyExceptions = Announcement::query()
            ->active()
            ->whereIn('priority', ['high', 'urgent'])
            ->whereDoesntHave('readers')
            ->count();

        if ($policyExceptions > 0) {
            $actions->push($this->action(
                'Check policy acknowledgement gaps',
                "{$policyExceptions} urgent update" . ($policyExceptions === 1 ? '' : 's') . ' with no receipts yet.',
                route('announcements.index', ['filter' => 'high-priority']),
                'Announcements',
                'amber',
                40
            ));
        }

        return $actions;
    }

    protected function adminActions(User $user): Collection
    {
        $actions = collect();

        $usersWithoutDepartment = User::query()
            ->whereNull('primary_department_id')
            ->where('role', '!=', 'admin')
            ->count();

        if ($usersWithoutDepartment > 0) {
            $actions->push($this->action(
                'Assign missing departments',
                "{$usersWithoutDepartment} user" . ($usersWithoutDepartment === 1 ? '' : 's') . ' need a primary department.',
                route('filament.admin.resources.users.index'),
                'Admin',
                'amber',
                10
            ));
        }

        if (config('app.debug')) {
            $actions->push($this->action(
                'Review debug configuration',
                'APP_DEBUG is enabled for this environment.',
                route('governance.index'),
                'Readiness',
                'rose',
                20
            ));
        }

        if (config('site_bulletin.demo_login_enabled') || config('site_bulletin.demo_simulation_enabled')) {
            $actions->push($this->action(
                'Check demo mode boundary',
                'Demo login or simulation is enabled.',
                route('governance.index'),
                'Readiness',
                'blue',
                30
            ));
        }

        $roleRequests = RoleChangeRequest::query()->pending()->count();

        if ($roleRequests > 0) {
            $actions->push($this->action(
                'Monitor pending role changes',
                "{$roleRequests} role request" . ($roleRequests === 1 ? '' : 's') . ' still pending.',
                route('role-requests.index'),
                'Governance',
                'slate',
                40
            ));
        }

        return $actions;
    }

    protected function scopedOpenTickets(User $user): Builder
    {
        return $this->roleScope
            ->applyViewableDepartmentScope(Ticket::query(), $user)
            ->open();
    }

    protected function latestUnreadConversation(User $user): ?Conversation
    {
        return Conversation::query()
            ->forUser($user)
            ->whereHas('participants', function (Builder $query) use ($user) {
                $query->where('users.id', $user->id)
                    ->where(function (Builder $sub) {
                        $sub->whereNull('conversation_participants.last_read_at')
                            ->orWhereColumn('conversation_participants.last_read_at', '<', 'conversations.updated_at');
                    });
            })
            ->orderByDesc('updated_at')
            ->first();
    }

    protected function action(
        string $title,
        string $detail,
        string $href,
        string $category,
        string $tone,
        int $priority
    ): array {
        return [
            'title' => $title,
            'detail' => $detail,
            'href' => $href,
            'category' => $category,
            'tone' => $tone,
            'priority' => $priority,
        ];
    }
}
