@extends('layouts.app')

@section('content')
  <div class="space-y-6">
    <header class="rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
      <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Governance</p>
      <h1 class="mt-2 text-2xl font-semibold text-slate-900">Operations Governance Hub</h1>
      <p class="mt-2 text-sm text-slate-600">
        Review approval queues, policy resources, and department contacts to keep the site compliant.
      </p>
    </header>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <article class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pending approvals</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $pendingApprovals }}</p>
        <p class="text-sm text-slate-500">Role change requests awaiting action.</p>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Policies</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">3</p>
        <p class="text-sm text-slate-500">Core documents refreshed this quarter.</p>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Departments</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $departments->count() }}</p>
        <p class="text-sm text-slate-500">Highlighting leadership contacts.</p>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Quick actions</p>
        <ul class="mt-3 space-y-1 text-sm text-blue-700">
          <li><a class="hover:underline" href="{{ route('governance.policies') }}">Review policies</a></li>
          <li><a class="hover:underline" href="{{ route('governance.organisation') }}">View org structure</a></li>
          <li><a class="hover:underline" href="{{ route('role-requests.create') }}">Request role change</a></li>
        </ul>
      </article>
    </section>

    @if (! empty($readinessReport))
      @php
        $statusClasses = [
            'ok' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
            'critical' => 'border-rose-200 bg-rose-50 text-rose-800',
        ];
        $summary = $readinessReport['summary'];
      @endphp
      <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Admin readiness</p>
            <h2 class="mt-1 text-lg font-semibold text-slate-900">System Health Checklist</h2>
            <p class="mt-1 text-sm text-slate-600">Production-facing configuration, scheduler inputs, and operational data checks.</p>
          </div>
          <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">OK</p>
              <p class="mt-1 text-2xl font-semibold text-emerald-900">{{ $summary['ok'] }}</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Warnings</p>
              <p class="mt-1 text-2xl font-semibold text-amber-900">{{ $summary['warning'] }}</p>
            </div>
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Critical</p>
              <p class="mt-1 text-2xl font-semibold text-rose-900">{{ $summary['critical'] }}</p>
            </div>
          </div>
        </div>

        <div class="mt-4 grid gap-3 xl:grid-cols-2">
          @foreach ($readinessReport['checks'] as $check)
            <article class="rounded-xl border px-4 py-3 {{ $statusClasses[$check['status']] ?? $statusClasses['warning'] }}">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="font-semibold">{{ $check['label'] }}</p>
                  <p class="mt-1 text-sm">{{ $check['detail'] }}</p>
                </div>
                <span class="rounded-full bg-white/70 px-2.5 py-1 text-[10px] font-semibold uppercase">{{ $check['status'] }}</span>
              </div>
              <p class="mt-2 text-xs">{{ $check['action'] }}</p>
            </article>
          @endforeach
        </div>
      </section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <header class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-900">Department contacts</h2>
        <a href="{{ route('governance.organisation') }}" class="text-sm font-medium text-blue-600 hover:underline">
          Full org chart
        </a>
      </header>
      <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($departments as $department)
          <article class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
            <h3 class="text-base font-semibold text-slate-900">{{ $department->name }}</h3>
            <p class="mt-1 text-xs uppercase tracking-wide text-slate-500">
              {{ $department->managers->isEmpty() ? 'No manager assigned' : 'Managers' }}
            </p>
            <ul class="mt-1 space-y-1 text-sm text-slate-600">
              @forelse ($department->managers as $manager)
                <li>{{ $manager->name }}</li>
              @empty
                <li class="text-xs text-slate-500">Assign via Admin &rarr; Departments</li>
              @endforelse
            </ul>
          </article>
        @endforeach
      </div>
    </section>
  </div>
@endsection
