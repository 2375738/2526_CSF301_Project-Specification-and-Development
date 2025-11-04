<?php

namespace App\Http\Controllers\Public;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\RoleChangeRequestStoreRequest;
use App\Models\Department;
use App\Models\RoleChangeRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleChangeRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = RoleChangeRequest::query()
            ->with(['requester:id,name', 'target:id,name', 'approver:id,name', 'department:id,name'])
            ->orderByDesc('created_at');

        if ($user->hasRole('hr', 'admin')) {
            $requests = $query->paginate(10);
        } else {
            $requests = $query
                ->where(fn ($q) => $q
                    ->where('requester_id', $user->id)
                    ->orWhere('target_user_id', $user->id))
                ->paginate(10);
        }

        return view('role-requests.index', [
            'requests' => $requests,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        [$targets, $departments] = $this->optionsFor($user);

        return view('role-requests.create', [
            'targets' => $targets,
            'departments' => $departments,
            'roles' => collect(UserRole::cases())->map(fn ($role) => [
                'value' => $role->value,
                'label' => ucfirst(str_replace('_', ' ', $role->value)),
            ]),
        ]);
    }

    public function store(RoleChangeRequestStoreRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();

        [$targets, $departments] = $this->optionsFor($user);

        abort_unless($targets->has($request->integer('target_user_id')), 403);

        $departmentId = $request->integer('department_id') ?: null;
        if ($departmentId) {
            abort_unless($departments->has($departmentId), 403);
        } else {
            $departmentId = User::find($request->integer('target_user_id'))
                ?->primary_department_id;
        }

        $roleRequest = RoleChangeRequest::create([
            'requester_id' => $user->id,
            'target_user_id' => $request->integer('target_user_id'),
            'requested_role' => $request->input('requested_role'),
            'department_id' => $departmentId,
            'justification' => $request->input('justification'),
            'status' => RoleChangeRequest::STATUS_PENDING,
        ]);

        $auditLogger->log('role.request.created', $roleRequest, [
            'requester_id' => $roleRequest->requester_id,
            'target_user_id' => $roleRequest->target_user_id,
            'requested_role' => $roleRequest->requested_role,
        ], $user);

        return redirect()
            ->route('role-requests.index')
            ->with('status', 'Role change request submitted for review.');
    }

    protected function optionsFor(User $user): array
    {
        if ($user->hasRole('hr', 'admin')) {
            $targets = User::query()->orderBy('name')->pluck('name', 'id');
            $departments = Department::query()->orderBy('name')->pluck('name', 'id');

            return [$targets, $departments];
        }

        if ($user->hasRole('manager', 'ops_manager')) {
            $managedDepartments = $user->managedDepartments()->pluck('departments.id');

            $targets = User::query()
                ->whereIn('role', [
                    UserRole::Employee->value,
                    UserRole::Manager->value,
                    UserRole::OpsManager->value,
                ])
                ->where(function ($query) use ($managedDepartments, $user) {
                    $query->where('id', $user->id)
                        ->orWhereHas('departments', fn ($q) => $q->whereIn('departments.id', $managedDepartments));
                })
                ->orderBy('name')
                ->pluck('name', 'id');

            $departmentOptions = Department::query()
                ->whereIn('id', $managedDepartments)
                ->orderBy('name')
                ->pluck('name', 'id');

            return [$targets, $departmentOptions];
        }

        $targets = collect([$user->id => $user->name]);

        $departmentOptions = $user->primary_department_id
            ? Department::query()
                ->where('id', $user->primary_department_id)
                ->pluck('name', 'id')
            : collect();

        return [$targets, $departmentOptions];
    }
}
