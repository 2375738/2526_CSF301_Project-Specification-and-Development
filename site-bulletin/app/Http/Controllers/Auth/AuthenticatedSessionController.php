<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        $demoLoginEnabled = $this->demoLoginEnabled();
        $departments = collect();
        $demoPresets = collect();

        if ($demoLoginEnabled) {
            $preferredSlugs = [
                'customer-returns',
                'kariba',
                'inbound',
                'icqa',
                'outbound',
                'support',
                'tom',
            ];

            $departments = Department::query()
                ->whereIn('slug', $preferredSlugs)
                ->get(['id', 'name', 'slug'])
                ->sortBy(function ($department) use ($preferredSlugs) {
                    return array_search($department->slug, $preferredSlugs, true);
                })
                ->values();

            if ($departments->isEmpty()) {
                $departments = Department::query()->orderBy('name')->get(['id', 'name']);
            }

            $demoPresets = $this->buildDemoPresets($departments);
        }

        return view('auth.login', [
            'demoLoginEnabled' => $demoLoginEnabled,
            'departments' => $departments,
            'demoPresets' => $demoPresets,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function demoStore(Request $request): RedirectResponse
    {
        abort_unless($this->demoLoginEnabled(), 403);

        $validated = $request->validate([
            'role' => ['required', Rule::in(['employee', 'manager'])],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        $user = User::query()
            ->where('role', $validated['role'])
            ->when(
                $validated['department_id'] ?? null,
                function ($query, $departmentId) {
                    $query->where(function ($inner) use ($departmentId) {
                        $inner->where('primary_department_id', $departmentId)
                            ->orWhereHas('departments', fn ($departmentQuery) => $departmentQuery
                                ->where('departments.id', $departmentId));
                    });
                }
            )
            ->orderBy('id')
            ->first();

        if (! $user) {
            return back()
                ->withInput()
                ->withErrors([
                    'role' => 'No demo user found for the selected role and department.',
                ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    protected function demoLoginEnabled(): bool
    {
        return app()->environment(['local', 'testing']) || (bool) config('app.debug');
    }

    protected function buildDemoPresets(Collection $departments): Collection
    {
        $departmentIds = $departments->pluck('id')->filter()->values();

        return collect([
            'employee' => 'Employee',
            'manager' => 'Manager',
        ])->map(function (string $label, string $role) use ($departmentIds) {
            $user = User::query()
                ->with('primaryDepartment:id,name')
                ->where('role', $role)
                ->when(
                    $departmentIds->isNotEmpty(),
                    fn ($query) => $query->whereIn('primary_department_id', $departmentIds)
                )
                ->orderBy('id')
                ->first()
                ?? User::query()
                    ->with('primaryDepartment:id,name')
                    ->where('role', $role)
                    ->orderBy('id')
                    ->first();

            if (! $user) {
                return null;
            }

            return [
                'role' => $role,
                'label' => $label,
                'name' => $user->name,
                'department_id' => $user->primary_department_id,
                'department_name' => $user->primaryDepartment?->name,
            ];
        })->filter()->values();
    }
}
