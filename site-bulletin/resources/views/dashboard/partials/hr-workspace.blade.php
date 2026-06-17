@php
    $hrWorkspace = $hrWorkspace ?? null;
    $summary = $hrWorkspace['summary'] ?? [];
    $pendingApprovals = collect($hrWorkspace['pending_approvals'] ?? []);
    $sensitiveCases = collect($hrWorkspace['sensitive_cases'] ?? []);
    $peopleTickets = collect($hrWorkspace['people_tickets'] ?? []);
    $roleRequests = collect($hrWorkspace['role_requests'] ?? []);
    $acknowledgementExceptions = collect($hrWorkspace['acknowledgement_exceptions'] ?? []);
    $sensitiveCategoryId = collect($hrWorkspace['sensitive_category_ids'] ?? [])->first();
@endphp

@auth
  @if (auth()->user()->hasRole('hr') && $hrWorkspace)
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-5">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">HR workspace</p>
          <h2 class="mt-1 text-lg font-semibold text-slate-900">People Operations Queue</h2>
          <p class="mt-1 text-sm text-slate-600">Approvals, sensitive cases, people tickets, and policy follow-up in one daily view.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <a href="{{ route('tickets.approvals.index', ['approver_role' => 'hr', 'status' => 'pending']) }}" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 transition hover:border-emerald-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">HR Approvals</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-900">{{ $summary['pending_approvals'] ?? 0 }}</p>
          </a>
          <a href="{{ route('tickets.index', array_filter(['category_id' => $sensitiveCategoryId])) }}" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 transition hover:border-rose-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Sensitive Cases</p>
            <p class="mt-1 text-2xl font-semibold text-rose-900">{{ $summary['sensitive_cases'] ?? 0 }}</p>
          </a>
          <a href="{{ route('tickets.index', ['category_id' => $sensitiveCategoryId]) }}" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 transition hover:border-blue-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">People Tickets</p>
            <p class="mt-1 text-2xl font-semibold text-blue-900">{{ $summary['people_tickets'] ?? 0 }}</p>
          </a>
          <a href="{{ route('announcements.index', ['sort' => 'priority']) }}" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 transition hover:border-amber-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Policy Follow-up</p>
            <p class="mt-1 text-2xl font-semibold text-amber-900">{{ $summary['acknowledgement_exceptions'] ?? 0 }}</p>
          </a>
        </div>
      </div>

      <div class="grid gap-4 xl:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-slate-900">Approvals Waiting Now</h3>
            <a href="{{ route('tickets.approvals.index', ['approver_role' => 'hr', 'status' => 'pending']) }}" class="text-xs font-semibold uppercase tracking-wide text-blue-600 hover:underline">Open approvals</a>
          </div>
          <div class="mt-3 space-y-2">
            @forelse ($pendingApprovals as $approval)
              <a href="{{ route('tickets.show', $approval['ticket_id']) }}" class="block rounded-xl border border-emerald-200 bg-white px-4 py-3 transition hover:border-emerald-300 hover:bg-emerald-50">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="font-semibold text-slate-900">{{ $approval['ticket_title'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $approval['requester_name'] }} · {{ $approval['department_name'] }} · {{ $approval['step_label'] }}</p>
                  </div>
                  <span class="text-xs text-slate-500">{{ $approval['updated_human'] }}</span>
                </div>
              </a>
            @empty
              <p class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-500">No HR approvals are waiting right now.</p>
            @endforelse
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-slate-900">Sensitive People Cases</h3>
            <a href="{{ route('tickets.index', array_filter(['category_id' => $sensitiveCategoryId])) }}" class="text-xs font-semibold uppercase tracking-wide text-blue-600 hover:underline">Open cases</a>
          </div>
          <div class="mt-3 space-y-2">
            @forelse ($sensitiveCases as $ticket)
              <a href="{{ route('tickets.show', $ticket['id']) }}" class="block rounded-xl border border-rose-200 bg-white px-4 py-3 transition hover:border-rose-300 hover:bg-rose-50">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="font-semibold text-slate-900">{{ $ticket['title'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $ticket['requester_name'] }} · {{ $ticket['department_name'] }}</p>
                  </div>
                  <span class="text-xs font-semibold text-rose-700">{{ $ticket['status_label'] }}</span>
                </div>
              </a>
            @empty
              <p class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-500">No sensitive cases are open.</p>
            @endforelse
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-slate-900">People Tickets</h3>
            <a href="{{ route('tickets.approvals.index', ['approver_role' => 'hr']) }}" class="text-xs font-semibold uppercase tracking-wide text-blue-600 hover:underline">Open workbench</a>
          </div>
          <div class="mt-3 space-y-2">
            @forelse ($peopleTickets as $ticket)
              <a href="{{ route('tickets.show', $ticket['id']) }}" class="block rounded-xl border border-blue-200 bg-white px-4 py-3 transition hover:border-blue-300 hover:bg-blue-50">
                <p class="font-semibold text-slate-900">{{ $ticket['title'] }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $ticket['category_name'] }} · {{ $ticket['requester_name'] }} · {{ $ticket['updated_human'] }}</p>
              </a>
            @empty
              <p class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-500">No open HR people tickets right now.</p>
            @endforelse
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-slate-900">Policy Follow-up</h3>
            <a href="{{ route('announcements.index', ['sort' => 'priority']) }}" class="text-xs font-semibold uppercase tracking-wide text-blue-600 hover:underline">Open updates</a>
          </div>
          <div class="mt-3 space-y-2">
            @forelse ($acknowledgementExceptions as $announcement)
              <a href="{{ route('announcements.show', $announcement['id']) }}" class="block rounded-xl border border-amber-200 bg-white px-4 py-3 transition hover:border-amber-300 hover:bg-amber-50">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="font-semibold text-slate-900">{{ $announcement['title'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $announcement['audience_label'] }} · {{ $announcement['read_count'] }} read · {{ $announcement['clarification_count'] }} need clarification</p>
                  </div>
                  <span class="text-xs font-semibold text-amber-700">{{ ucfirst($announcement['priority']) }}</span>
                </div>
              </a>
            @empty
              <p class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-500">No policy acknowledgement exceptions need follow-up.</p>
            @endforelse
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-sm font-semibold text-slate-900">Role Requests</h3>
          <a href="{{ route('role-requests.index') }}" class="text-xs font-semibold uppercase tracking-wide text-blue-600 hover:underline">Open governance</a>
        </div>
        <div class="mt-3 grid gap-2 xl:grid-cols-2">
          @forelse ($roleRequests as $request)
            <a href="{{ route('role-requests.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm transition hover:border-blue-300 hover:bg-blue-50">
              <p class="font-semibold text-slate-900">{{ $request['target_name'] }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ $request['requested_role'] }} · {{ $request['department_name'] }} · {{ $request['created_human'] }}</p>
            </a>
          @empty
            <p class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-500">No pending role requests.</p>
          @endforelse
        </div>
      </div>
    </section>
  @endif
@endauth
