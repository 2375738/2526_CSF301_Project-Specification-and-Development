@extends('layouts.app')

@section('content')
  <div class="space-y-6">
    <div class="rounded-xl bg-white px-6 py-5 shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h1 class="text-xl font-semibold text-slate-900">Approval Workbench</h1>
          <p class="mt-1 text-sm text-slate-600">
            Review request-style tickets that need manager or HR decisions and track what is queued behind them.
          </p>
        </div>
        <div class="flex flex-wrap gap-3">
          <a
            href="{{ route('tickets.index') }}"
            class="inline-flex items-center justify-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          >
            Back to tickets
          </a>
        </div>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-xl bg-white px-5 py-4 shadow-sm ring-1 ring-slate-200">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Waiting now</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $summary['pending'] }}</p>
        <p class="mt-1 text-sm text-slate-600">Approvals that can be decided immediately.</p>
      </div>
      <div class="rounded-xl bg-white px-5 py-4 shadow-sm ring-1 ring-slate-200">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Queued next</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $summary['queued'] }}</p>
        <p class="mt-1 text-sm text-slate-600">Follow-on steps waiting for an earlier approval.</p>
      </div>
      <div class="rounded-xl bg-white px-5 py-4 shadow-sm ring-1 ring-slate-200">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Needs info</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $summary['needs_info'] }}</p>
        <p class="mt-1 text-sm text-slate-600">Requests paused until the requester adds more detail.</p>
      </div>
      <div class="rounded-xl bg-white px-5 py-4 shadow-sm ring-1 ring-slate-200">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Completed</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $summary['completed'] }}</p>
        <p class="mt-1 text-sm text-slate-600">Approval steps already decided in the current queue scope.</p>
      </div>
    </div>

    <form method="GET" action="{{ route('tickets.approvals.index') }}" class="rounded-xl bg-white px-6 py-4 shadow-sm">
      <div class="flex flex-wrap items-center gap-3">
        <label class="min-w-[200px] flex-1 text-sm text-slate-600">
          <span class="sr-only">Search approvals</span>
          <input
            type="search"
            name="search"
            value="{{ $filters['search'] ?? '' }}"
            placeholder="Search ticket, requester, or approval step..."
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
          />
        </label>

        <label class="text-sm text-slate-600">
          <span class="sr-only">Approval status</span>
          <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">All statuses</option>
            @foreach (['pending', 'queued', 'needs_info', 'approved', 'rejected'] as $status)
              <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
            @endforeach
          </select>
        </label>

        <label class="text-sm text-slate-600">
          <span class="sr-only">Approver role</span>
          <select name="approver_role" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">All reviewer groups</option>
            @foreach (['manager', 'hr'] as $role)
              <option value="{{ $role }}" @selected(($filters['approver_role'] ?? '') === $role)>{{ strtoupper($role) }}</option>
            @endforeach
          </select>
        </label>

        @if ($stepOptions->isNotEmpty())
          <label class="text-sm text-slate-600">
            <span class="sr-only">Step</span>
            <select name="step_key" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
              <option value="">All steps</option>
              @foreach ($stepOptions as $stepKey)
                <option value="{{ $stepKey }}" @selected(($filters['step_key'] ?? '') === $stepKey)>{{ ucfirst(str_replace('_', ' ', $stepKey)) }}</option>
              @endforeach
            </select>
          </label>
        @endif

        @if ($departmentOptions->isNotEmpty())
          <label class="text-sm text-slate-600">
            <span class="sr-only">Department</span>
            <select name="department_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
              <option value="">All departments</option>
              @foreach ($departmentOptions as $id => $name)
                <option value="{{ $id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $id)>{{ $name }}</option>
              @endforeach
            </select>
          </label>
        @endif

        <button type="submit" class="inline-flex items-center rounded-full bg-slate-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-900">
          Apply
        </button>
      </div>
    </form>

    @if (($priorityBuckets['waiting_now'] ?? collect())->isNotEmpty() || ($priorityBuckets['paused'] ?? collect())->isNotEmpty() || ($priorityBuckets['recently_completed'] ?? collect())->isNotEmpty())
      <div class="grid gap-4 xl:grid-cols-3">
        <section class="rounded-xl bg-white px-5 py-4 shadow-sm">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Waiting Now</h2>
              <p class="mt-1 text-sm text-slate-600">The first approvals that can be decided immediately.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ ($priorityBuckets['waiting_now'] ?? collect())->count() }}</span>
          </div>
          <div class="mt-4 space-y-3">
            @forelse (($priorityBuckets['waiting_now'] ?? collect()) as $approval)
              <a href="{{ route('tickets.show', $approval->ticket) }}" class="block rounded-lg border border-slate-200 px-3 py-3 hover:border-blue-300 hover:bg-blue-50">
                <p class="text-sm font-semibold text-slate-900">{{ $approval->ticket->title }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ ucfirst(str_replace('_', ' ', $approval->step_key)) }} · {{ strtoupper($approval->approver_role) }}</p>
                <p class="mt-2 text-sm text-slate-700">{{ $approval->ticket->requester?->name ?? 'Requester unavailable' }}</p>
              </a>
            @empty
              <p class="rounded-lg border border-dashed border-slate-300 px-3 py-4 text-sm text-slate-500">Nothing actionable in this scope right now.</p>
            @endforelse
          </div>
        </section>

        <section class="rounded-xl bg-white px-5 py-4 shadow-sm">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Paused Waiting On Requester</h2>
              <p class="mt-1 text-sm text-slate-600">Approvals that cannot move until more detail comes back.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">{{ ($priorityBuckets['paused'] ?? collect())->count() }}</span>
          </div>
          <div class="mt-4 space-y-3">
            @forelse (($priorityBuckets['paused'] ?? collect()) as $approval)
              <a href="{{ route('tickets.show', $approval->ticket) }}" class="block rounded-lg border border-amber-200 px-3 py-3 hover:bg-amber-50">
                <p class="text-sm font-semibold text-slate-900">{{ $approval->ticket->title }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $approval->ticket->requester?->name ?? 'Requester unavailable' }} · Updated {{ $approval->updated_at->diffForHumans() }}</p>
                <p class="mt-2 text-sm text-amber-900">{{ $approval->public_note ?: 'More information was requested on this approval.' }}</p>
              </a>
            @empty
              <p class="rounded-lg border border-dashed border-slate-300 px-3 py-4 text-sm text-slate-500">No approvals are paused on requester detail.</p>
            @endforelse
          </div>
        </section>

        <section class="rounded-xl bg-white px-5 py-4 shadow-sm">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Completed Recently</h2>
              <p class="mt-1 text-sm text-slate-600">Recent outcomes that may still need review or follow-up.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">{{ ($priorityBuckets['recently_completed'] ?? collect())->count() }}</span>
          </div>
          <div class="mt-4 space-y-3">
            @forelse (($priorityBuckets['recently_completed'] ?? collect()) as $approval)
              <a href="{{ route('tickets.show', $approval->ticket) }}" class="block rounded-lg border border-slate-200 px-3 py-3 hover:border-blue-300 hover:bg-blue-50">
                <p class="text-sm font-semibold text-slate-900">{{ $approval->ticket->title }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $approval->publicStatusLabel() }} · {{ optional($approval->decided_at)->diffForHumans() ?? $approval->updated_at->diffForHumans() }}</p>
                <p class="mt-2 text-sm text-slate-700">{{ $approval->public_note ?: 'Decision recorded on this approval step.' }}</p>
              </a>
            @empty
              <p class="rounded-lg border border-dashed border-slate-300 px-3 py-4 text-sm text-slate-500">No completed approvals in the current scope.</p>
            @endforelse
          </div>
        </section>
      </div>
    @endif

    @if ($approvals->isEmpty())
      <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-slate-500">
        No approvals matched the current scope or filters.
      </div>
    @else
      <div class="space-y-4">
        @foreach ($approvals as $approval)
          @include('tickets.partials.approval-card', ['approval' => $approval])
        @endforeach
      </div>

      <div>
        {{ $approvals->links() }}
      </div>
    @endif
  </div>
@endsection
