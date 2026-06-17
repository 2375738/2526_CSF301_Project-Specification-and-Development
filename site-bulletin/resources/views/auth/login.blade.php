<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="space-y-6">
        @if (($demoLoginEnabled ?? false) && ($demoPresets ?? collect())->isNotEmpty())
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 shadow-sm">
                <div class="space-y-1">
                    <h2 class="text-base font-semibold text-slate-900">Quick Demo Access</h2>
                    <p class="text-sm text-slate-600">
                        Enter as any seeded role, or use the custom selector below for department-specific employee and manager checks.
                    </p>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($demoPresets as $preset)
                        <form method="POST" action="{{ route('demo.login') }}">
                            @csrf
                            <input type="hidden" name="role" value="{{ $preset['role'] }}">
                            @if (!empty($preset['department_id']))
                                <input type="hidden" name="department_id" value="{{ $preset['department_id'] }}">
                            @endif
                            <button
                                type="submit"
                                class="flex min-h-[88px] w-full flex-col items-start justify-center rounded-lg border border-slate-200 bg-white px-4 py-3 text-left shadow-sm transition hover:border-blue-200 hover:bg-blue-50"
                            >
                                <span class="text-sm font-semibold text-slate-900">Enter as {{ $preset['label'] }}</span>
                                <span class="mt-1 text-sm text-slate-600">{{ $preset['name'] }}</span>
                                <span class="mt-2 text-xs uppercase tracking-[0.2em] text-slate-500">
                                    {{ $preset['department_name'] ?? 'Any Department' }}
                                </span>
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div class="mt-4">
                <x-input-label for="password" :value="__('Password')" />

                <x-text-input id="password" class="block mt-1 w-full"
                                type="password"
                                name="password"
                                required autocomplete="current-password" />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Remember Me -->
            <div class="block mt-4">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                    <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                @if (Route::has('password.request'))
                    <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif

                <x-primary-button class="ms-3">
                    {{ __('Log in') }}
                </x-primary-button>
            </div>
        </form>

        @if (($demoLoginEnabled ?? false) && ($departments ?? collect())->isNotEmpty())
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <h2 class="text-sm font-semibold text-slate-900">Custom Demo Role</h2>
                <p class="mt-1 text-xs text-slate-600">
                    Select role and department to enter the app without manual credentials. Department applies to employee and manager demos.
                </p>
                <form method="POST" action="{{ route('demo.login') }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <x-input-label for="demo_role" :value="__('Role')" />
                        <select
                            id="demo_role"
                            name="role"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option value="employee" @selected(old('role') === 'employee')>Employee</option>
                            <option value="manager" @selected(old('role') === 'manager')>Manager</option>
                            <option value="ops_manager" @selected(old('role') === 'ops_manager')>Ops Manager</option>
                            <option value="hr" @selected(old('role') === 'hr')>HR</option>
                            <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="demo_department_id" :value="__('Department')" />
                        <select
                            id="demo_department_id"
                            name="department_id"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Any Department</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
                    </div>
                    <x-primary-button class="w-full justify-center">
                        Enter Demo
                    </x-primary-button>
                </form>
            </div>
        @endif
    </div>
</x-guest-layout>
