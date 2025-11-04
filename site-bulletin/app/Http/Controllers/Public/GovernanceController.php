<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Department;
use App\Models\RoleChangeRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GovernanceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $pendingApprovals = RoleChangeRequest::query()
            ->when(
                $user->hasRole('hr', 'admin'),
                fn ($query) => $query->pending(),
                fn ($query) => $query->where(fn ($inner) => $inner
                    ->where('requester_id', $user->id)
                    ->orWhere('target_user_id', $user->id))
            )
            ->count();

        $departments = Department::query()
            ->with(['managers' => fn ($q) => $q->orderBy('users.name')])
            ->orderBy('name')
            ->take(6)
            ->get();

        return view('governance.index', [
            'pendingApprovals' => $pendingApprovals,
            'departments' => $departments,
        ]);
    }

    public function policies(): View
    {
        $policies = [
            [
                'title' => 'Code of Conduct',
                'summary' => 'Standards for respectful workplace behaviour, reporting, and enforcement.',
            ],
            [
                'title' => 'Health & Safety Escalation',
                'summary' => 'Immediate actions, documentation, and notification chain for safety incidents.',
            ],
            [
                'title' => 'Security & Access',
                'summary' => 'Badge usage, visitor management, and access revocation guidelines.',
            ],
        ];

        return view('governance.policies', [
            'policies' => $policies,
        ]);
    }

    public function organisation(): View
    {
        $departments = Department::query()
            ->with([
                'managers' => fn ($q) => $q->orderBy('users.name'),
                'members' => fn ($q) => $q->orderBy('users.name'),
            ])
            ->orderBy('name')
            ->get();

        return view('governance.organisation', [
            'departments' => $departments,
        ]);
    }

    public function escalation(): View
    {
        $categories = Category::query()
            ->with(['department.managers' => fn ($q) => $q->orderBy('users.name')])
            ->orderBy('name')
            ->get();

        $playbook = [
            'Critical safety issue' => [
                'Log a ticket with Critical priority.',
                'Notify site manager via Messages Department broadcast.',
                'Ensure audit log entry confirms escalation.',
            ],
            'HR incident' => [
                'Create a ticket in HR category with confidentiality toggle.',
                'Message HR lead directly for context.',
                'Follow up with role change request if permissions need adjusting.',
            ],
        ];

        return view('governance.escalation', [
            'categories' => $categories,
            'playbook' => $playbook,
        ]);
    }
}
