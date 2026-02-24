<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        return view('auth.login', [
            'demoLoginEnabled' => $demoLoginEnabled,
            'departments' => $demoLoginEnabled
                ? Department::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
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
}
