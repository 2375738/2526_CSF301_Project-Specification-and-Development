@extends('layouts.app')

@section('content')
@php
    $initials = collect(explode(' ', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');

    $roleLabel = $user->role?->label()
        ?? ($user->role ? str($user->role)->replace('_', ' ')->title()->toString() : 'Employee');
@endphp

<div class="space-y-6">
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
            <div class="flex min-w-0 items-start gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-sm font-semibold text-white">
                    {{ $initials }}
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="truncate text-2xl font-semibold text-slate-900">{{ $user->name }}</h1>
                        @if($user->pronouns)
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $user->pronouns }}</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm font-medium text-slate-700">{{ $user->job_title ?? 'Associate' }}</p>
                    <p class="text-sm text-slate-500">{{ $roleLabel }} · {{ $user->primaryDepartment->name ?? 'No department assigned' }}</p>
                </div>
            </div>

            <div class="grid gap-2 text-sm text-slate-600 sm:grid-cols-2 md:min-w-80">
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Employee ID</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $user->employee_id ?? '-' }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Site</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $user->location ?? '-' }}</p>
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.4fr)]">
        <aside class="space-y-6">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Work Profile</h2>
                <dl class="mt-4 space-y-4 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Login</dt>
                        <dd class="mt-1 break-all font-medium text-slate-900">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Department</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ $user->primaryDepartment->name ?? 'Not assigned' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Manager</dt>
                        <dd class="mt-1">
                            @if($manager)
                                <span class="font-medium text-slate-900">{{ $manager->name }}</span>
                                <span class="block break-all text-xs text-slate-500">{{ $manager->email }}</span>
                            @else
                                <span class="text-slate-500">No manager assigned</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Phone</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ $user->phone ?? '-' }}</dd>
                    </div>
                </dl>
            </section>

            @if(! empty($jobHistory))
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Recent Role History</h2>
                    <div class="mt-4 space-y-4">
                        @foreach(collect($jobHistory)->take(3) as $job)
                            <div class="border-l-2 {{ $job['status'] === 'current' ? 'border-blue-600' : 'border-slate-200' }} pl-3 text-sm">
                                <p class="font-semibold text-slate-900">{{ $job['role'] }}</p>
                                <p class="text-xs text-slate-500">{{ $job['department'] }} · {{ $job['start_date'] }} · {{ $job['duration'] }}</p>
                                <p class="text-xs text-slate-500">Manager: {{ $job['manager'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </aside>

        <section class="space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" id="personal">
                @include('profile.partials.update-profile-information-form')
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    @include('profile.partials.update-password-form')
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
