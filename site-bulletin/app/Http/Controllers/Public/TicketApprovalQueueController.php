<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\TicketApproval;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TicketApprovalQueueController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $baseQuery = $this->scopedApprovalsQuery($user);

        $approvalQuery = (clone $baseQuery)
            ->with([
                'ticket.requester:id,name',
                'ticket.createdFor:id,name',
                'ticket.department:id,name',
                'ticket.category:id,name',
                'approver:id,name',
            ])
            ->when(
                $request->filled('status'),
                fn (Builder $query) => $query->where('status', $request->string('status'))
            )
            ->when(
                $request->filled('approver_role'),
                fn (Builder $query) => $query->where('approver_role', $request->string('approver_role'))
            )
            ->when(
                $request->filled('step_key'),
                fn (Builder $query) => $query->where('step_key', $request->string('step_key'))
            )
            ->when(
                $request->filled('department_id'),
                fn (Builder $query) => $query->whereHas(
                    'ticket',
                    fn (Builder $ticketQuery) => $ticketQuery->where('department_id', $request->integer('department_id'))
                )
            )
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $term = '%' . trim((string) $request->input('search')) . '%';

                $query->where(function (Builder $searchQuery) use ($term) {
                    $searchQuery->where('step_key', 'like', $term)
                        ->orWhereHas('ticket', function (Builder $ticketQuery) use ($term) {
                            $ticketQuery->where('title', 'like', $term)
                                ->orWhereHas('requester', fn (Builder $requesterQuery) => $requesterQuery->where('name', 'like', $term))
                                ->orWhereHas('createdFor', fn (Builder $createdForQuery) => $createdForQuery->where('name', 'like', $term));
                        });
                });
            });

        $approvals = $approvalQuery
            ->orderByRaw("case when status = 'pending' then 0 when status = 'queued' then 1 when status = 'needs_info' then 2 else 3 end")
            ->orderBy('step_order')
            ->orderByDesc('updated_at')
            ->paginate(12)
            ->withQueryString();

        $priorityBuckets = [
            'waiting_now' => (clone $approvalQuery)
                ->where('status', TicketApproval::STATUS_PENDING)
                ->orderBy('step_order')
                ->orderByDesc('updated_at')
                ->limit(3)
                ->get(),
            'paused' => (clone $approvalQuery)
                ->where('status', TicketApproval::STATUS_NEEDS_INFO)
                ->orderByDesc('updated_at')
                ->limit(3)
                ->get(),
            'recently_completed' => (clone $approvalQuery)
                ->whereIn('status', [TicketApproval::STATUS_APPROVED, TicketApproval::STATUS_REJECTED])
                ->orderByDesc('decided_at')
                ->orderByDesc('updated_at')
                ->limit(3)
                ->get(),
        ];

        $summary = [
            'pending' => (clone $baseQuery)->where('status', TicketApproval::STATUS_PENDING)->count(),
            'queued' => (clone $baseQuery)->where('status', TicketApproval::STATUS_QUEUED)->count(),
            'needs_info' => (clone $baseQuery)->where('status', TicketApproval::STATUS_NEEDS_INFO)->count(),
            'completed' => (clone $baseQuery)->whereIn('status', [TicketApproval::STATUS_APPROVED, TicketApproval::STATUS_REJECTED])->count(),
        ];

        $departmentOptions = $this->departmentOptions($user);
        $stepOptions = (clone $baseQuery)
            ->select('step_key')
            ->distinct()
            ->orderBy('step_key')
            ->pluck('step_key');

        return view('tickets.approvals', [
            'approvals' => $approvals,
            'summary' => $summary,
            'priorityBuckets' => $priorityBuckets,
            'filters' => $request->only(['status', 'approver_role', 'step_key', 'department_id', 'search']),
            'departmentOptions' => $departmentOptions,
            'stepOptions' => $stepOptions,
        ]);
    }

    protected function scopedApprovalsQuery(User $user): Builder
    {
        $query = TicketApproval::query()->with('ticket');

        if ($user->hasRole('hr', 'admin')) {
            return $query;
        }

        $managedDepartmentIds = $user->managedDepartments()->pluck('departments.id');

        if ($managedDepartmentIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('approver_role', 'manager')
            ->whereHas('ticket', fn (Builder $ticketQuery) => $ticketQuery->whereIn('department_id', $managedDepartmentIds));
    }

    protected function departmentOptions(User $user)
    {
        if ($user->hasRole('hr', 'admin')) {
            return Department::query()->orderBy('name')->pluck('name', 'id');
        }

        return $user->managedDepartments()
            ->orderBy('departments.name')
            ->pluck('departments.name', 'departments.id');
    }
}
