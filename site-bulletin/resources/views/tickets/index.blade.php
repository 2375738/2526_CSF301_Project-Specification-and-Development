@extends('layouts.app')

@section('content')
  <div class="space-y-6">
    <div class="bg-white shadow-sm rounded-xl px-6 py-5">
      <h1 class="text-xl font-semibold text-slate-900">My Tickets</h1>
      <p class="mt-1 text-sm text-slate-600">
        Track open issues, see status updates, and share more details with the site team.
      </p>
    </div>

    <form method="GET" action="{{ route('tickets.index') }}" class="bg-white shadow-sm rounded-xl px-6 py-4 flex flex-wrap items-center gap-3">
      <label class="flex-1 min-w-[160px] text-sm text-slate-600">
        <span class="sr-only">Search</span>
        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search tickets..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" />
      </label>

      <label class="text-sm text-slate-600">
        <span class="sr-only">Status</span>
        <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
          <option value="">All statuses</option>
          @foreach (['new','triaged','in_progress','waiting_employee','resolved','closed','reopened','cancelled'] as $status)
            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
          @endforeach
        </select>
      </label>

      @if (isset($departmentFilterOptions) && $departmentFilterOptions->isNotEmpty())
        <label class="text-sm text-slate-600">
          <span class="sr-only">Department</span>
          <select name="department_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">All departments</option>
            @foreach ($departmentFilterOptions as $id => $name)
              <option value="{{ $id }}" @selected(($filters['department_id'] ?? '') == $id)>{{ $name }}</option>
            @endforeach
          </select>
        </label>
      @endif

      @if (auth()->user()->isManager() || auth()->user()->isHr())
        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
          <input type="checkbox" name="mine" value="1" @checked(($filters['mine'] ?? false)) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
          Assigned to me
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
          <input type="checkbox" name="overdue" value="1" @checked(($filters['overdue'] ?? false)) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
          SLA overdue
        </label>
      @endif

      <button type="submit" class="inline-flex items-center rounded-full bg-slate-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-900">
        Apply
      </button>
    </form>

    @if ($tickets->isEmpty())
      <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-slate-500">
        No tickets found. Try adjusting your filters or <a href="{{ route('tickets.create') }}" class="font-semibold text-blue-600 hover:underline">report a new issue</a>.
      </div>
    @else
      <div class="space-y-4">
        @foreach ($tickets as $ticket)
          <article class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
              <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">#{{ $ticket->id }}</span>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-700">
                  {{ ucfirst($ticket->priority->value ?? $ticket->priority) }}
                </span>
                <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-blue-700">
                  {{ ucfirst(str_replace('_', ' ', $ticket->status->value ?? $ticket->status)) }}
                </span>
                @if (($ticket->sla_first_response_breached ?? false) || ($ticket->sla_resolution_breached ?? false))
                  <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-rose-700">SLA Breach</span>
                @endif
              </div>
              <h2 class="mt-1 text-base font-semibold text-slate-900">
                <a href="{{ route('tickets.show', $ticket) }}" class="hover:underline">{{ $ticket->title }}</a>
              </h2>
              <p class="text-xs text-slate-500">
                {{ $ticket->category?->name ?? 'Uncategorised' }} • Opened {{ $ticket->created_at->diffForHumans() }}
                @if ($ticket->createdFor && $ticket->created_for_id !== $ticket->requester_id)
                  • For {{ $ticket->createdFor->name }}
                @endif
                @if ($ticket->department)
                  • {{ $ticket->department->name }}
                @endif
              </p>
            </div>
            <div class="flex flex-col items-start gap-2 text-sm text-slate-600 md:items-end">
              @if ($ticket->assignee)
                <p>Assignee: <span class="font-medium text-slate-800">{{ $ticket->assignee->name }}</span></p>
              @endif
              <a href="{{ route('tickets.show', $ticket) }}" class="inline-flex items-center rounded-full bg-slate-200 px-3 py-1 text-sm font-medium text-slate-700 hover:bg-slate-300">
                View details
              </a>
            </div>
          </article>
        @endforeach
      </div>

      <div>
        {{ $tickets->links() }}
      </div>
    @endif
  </div>
@endsection
