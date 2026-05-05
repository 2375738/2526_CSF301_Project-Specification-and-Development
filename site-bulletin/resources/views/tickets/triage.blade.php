@extends('layouts.app')

@section('content')
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <a href="{{ route('tickets.index') }}" class="text-sm font-medium text-blue-600 hover:underline">
        Back to tasks
      </a>
      <a href="{{ route('home') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">
        Dashboard
      </a>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900">Support Triage Board</h1>
          <p class="mt-1 text-sm text-slate-600">Queue-oriented view for support and admin work that needs ownership, intervention, or response.</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <a href="{{ route('tickets.index') }}" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Open Tickets
          </a>
          <a href="{{ route('analytics.index') }}" class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
            Analytics
          </a>
        </div>
      </div>
      <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
        Use this board to assign owners, intervene on breaches, and rebalance support load before tickets stall.
      </div>
    </div>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Unassigned New</p>
        <p class="mt-2 text-2xl font-semibold text-amber-900">{{ $unassignedNew->count() }}</p>
      </div>
      <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Breached Queue</p>
        <p class="mt-2 text-2xl font-semibold text-rose-900">{{ $breachedQueue->count() }}</p>
      </div>
      <div class="rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Waiting On Employee</p>
        <p class="mt-2 text-2xl font-semibold text-blue-900">{{ $waitingOnEmployee->count() }}</p>
      </div>
      <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Recently Updated</p>
        <p class="mt-2 text-2xl font-semibold text-emerald-900">{{ $recentlyUpdated->count() }}</p>
      </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
      <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">Unassigned New</h2>
          <a href="{{ route('tickets.index', ['status' => 'new']) }}" class="text-xs font-semibold uppercase tracking-wide text-blue-600 hover:underline">Open queue</a>
        </div>
        <div class="mt-4 space-y-3">
          @forelse ($unassignedNew as $ticket)
            <a href="{{ route('tickets.show', $ticket) }}" class="block rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 transition hover:border-amber-300">
              <p class="font-semibold text-slate-900">{{ $ticket->title }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ $ticket->category?->name ?? 'Uncategorised' }} · {{ $ticket->department?->name ?? 'No department' }} · {{ $ticket->updated_at->diffForHumans() }}</p>
            </a>
          @empty
            <p class="rounded-xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">No unassigned new tickets in the current triage scope.</p>
          @endforelse
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">Breached Queue</h2>
          <a href="{{ route('tickets.index', ['breached' => 1]) }}" class="text-xs font-semibold uppercase tracking-wide text-blue-600 hover:underline">Open queue</a>
        </div>
        <div class="mt-4 space-y-3">
          @forelse ($breachedQueue as $ticket)
            <a href="{{ route('tickets.show', $ticket) }}" class="block rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 transition hover:border-rose-300">
              <p class="font-semibold text-slate-900">{{ $ticket->title }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ $ticket->category?->name ?? 'Uncategorised' }} · {{ $ticket->assignee?->name ?? 'Unassigned' }} · {{ $ticket->updated_at->diffForHumans() }}</p>
            </a>
          @empty
            <p class="rounded-xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">No breached tickets in the current triage scope.</p>
          @endforelse
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">Waiting On Employee</h2>
          <a href="{{ route('tickets.index', ['status' => 'waiting_employee']) }}" class="text-xs font-semibold uppercase tracking-wide text-blue-600 hover:underline">Open queue</a>
        </div>
        <div class="mt-4 space-y-3">
          @forelse ($waitingOnEmployee as $ticket)
            <a href="{{ route('tickets.show', $ticket) }}" class="block rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 transition hover:border-blue-300">
              <p class="font-semibold text-slate-900">{{ $ticket->title }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ $ticket->requester?->name ?? 'Unknown requester' }} · {{ $ticket->department?->name ?? 'No department' }} · {{ $ticket->updated_at->diffForHumans() }}</p>
            </a>
          @empty
            <p class="rounded-xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">No tickets are waiting on employee follow-up.</p>
          @endforelse
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">Recently Updated</h2>
          <a href="{{ route('tickets.index') }}" class="text-xs font-semibold uppercase tracking-wide text-blue-600 hover:underline">Open queue</a>
        </div>
        <div class="mt-4 space-y-3">
          @forelse ($recentlyUpdated as $ticket)
            <a href="{{ route('tickets.show', $ticket) }}" class="block rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 transition hover:border-emerald-300">
              <p class="font-semibold text-slate-900">{{ $ticket->title }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ ucfirst(str_replace('_', ' ', $ticket->status->value ?? $ticket->status)) }} · {{ $ticket->assignee?->name ?? 'Unassigned' }} · {{ $ticket->updated_at->diffForHumans() }}</p>
            </a>
          @empty
            <p class="rounded-xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">No recent open-ticket activity in the current triage scope.</p>
          @endforelse
        </div>
      </section>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
      <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">Ownership Load</h2>
          <span class="text-xs text-slate-500">Open tickets by assignee</span>
        </div>
        <div class="mt-4 space-y-2">
          @forelse ($ownershipSummary as $row)
            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
              <span class="font-medium text-slate-800">{{ $row['assignee_name'] }}</span>
              <span class="font-semibold text-slate-900">{{ $row['total'] }}</span>
            </div>
          @empty
            <p class="text-sm text-slate-500">No ownership data available.</p>
          @endforelse
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">By Category</h2>
          <span class="text-xs text-slate-500">Open queue mix</span>
        </div>
        <div class="mt-4 space-y-2">
          @forelse ($categorySummary as $row)
            <a href="{{ route('tickets.index', ['category_id' => $row['category_id']]) }}" class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm transition hover:border-blue-300 hover:bg-blue-50">
              <span class="font-medium text-slate-800">{{ $row['category_name'] }}</span>
              <span class="font-semibold text-slate-900">{{ $row['total'] }}</span>
            </a>
          @empty
            <p class="text-sm text-slate-500">No category summary available.</p>
          @endforelse
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">By Department</h2>
          <span class="text-xs text-slate-500">Scoped open workload</span>
        </div>
        <div class="mt-4 space-y-2">
          @forelse ($departmentSummary as $row)
            <a href="{{ route('tickets.index', ['department_id' => $row['department_id']]) }}" class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm transition hover:border-blue-300 hover:bg-blue-50">
              <span class="font-medium text-slate-800">{{ $row['department_name'] }}</span>
              <span class="font-semibold text-slate-900">{{ $row['total'] }}</span>
            </a>
          @empty
            <p class="text-sm text-slate-500">No department summary available.</p>
          @endforelse
        </div>
      </section>
    </div>
  </div>
@endsection
