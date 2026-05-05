@php
    $managerAttentionQueue = $managerAttentionQueue ?? null;
@endphp

@auth
  @if (auth()->user()->hasRole('manager', 'ops_manager') && $managerAttentionQueue)
    @php
      $queueDepartmentId = $managerAttentionQueue['department_id'] ?? null;
    @endphp
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-5">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h2 class="text-lg font-semibold text-slate-900">Attention Queue</h2>
          <p class="mt-1 text-sm text-slate-600">Current shift decision queue from simulated live tickets, messages, and role requests.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <div class="rounded-xl border border-orange-200 bg-orange-50 px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Breached Tickets</p>
            <p class="mt-1 text-2xl font-semibold text-orange-900">{{ $managerAttentionQueue['breached_count'] }}</p>
          </div>
          <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Waiting On Employee</p>
            <p class="mt-1 text-2xl font-semibold text-amber-900">{{ $managerAttentionQueue['waiting_count'] }}</p>
          </div>
          <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Unread Messages</p>
            <p class="mt-1 text-2xl font-semibold text-blue-900">{{ $managerAttentionQueue['unread_count'] }}</p>
          </div>
          <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Pending Requests</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-900">{{ $managerAttentionQueue['pending_request_count'] }}</p>
          </div>
        </div>
      </div>

      <div class="grid gap-4 xl:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-slate-900">Breached Tickets</h3>
            <a href="{{ route('tickets.index', array_filter(['breached' => 1, 'department_id' => $queueDepartmentId])) }}" class="text-xs font-semibold uppercase tracking-wide text-blue-600 hover:underline">Open queue</a>
          </div>
          <div class="mt-3 space-y-2">
            @forelse ($managerAttentionQueue['breached_tickets'] as $ticket)
              <a href="{{ route('tickets.show', $ticket['id']) }}" class="block rounded-xl border border-orange-200 bg-white px-4 py-3 transition hover:border-orange-300 hover:bg-orange-50">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="font-semibold text-slate-900">{{ $ticket['title'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $ticket['category_name'] }} · {{ $ticket['requester_name'] ?? 'Unknown requester' }}</p>
                  </div>
                  <span class="text-xs text-slate-500">{{ $ticket['updated_human'] }}</span>
                </div>
              </a>
            @empty
              <p class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-500">No breached tickets in the current department queue.</p>
            @endforelse
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-slate-900">Waiting On Employee</h3>
            <a href="{{ route('tickets.index', array_filter(['status' => 'waiting_employee', 'department_id' => $queueDepartmentId])) }}" class="text-xs font-semibold uppercase tracking-wide text-blue-600 hover:underline">Open queue</a>
          </div>
          <div class="mt-3 space-y-2">
            @forelse ($managerAttentionQueue['waiting_on_employees'] as $ticket)
              <a href="{{ route('tickets.show', $ticket['id']) }}" class="block rounded-xl border border-amber-200 bg-white px-4 py-3 transition hover:border-amber-300 hover:bg-amber-50">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="font-semibold text-slate-900">{{ $ticket['title'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $ticket['category_name'] }} · {{ $ticket['requester_name'] ?? 'Unknown requester' }}</p>
                  </div>
                  <span class="text-xs text-slate-500">{{ $ticket['updated_human'] }}</span>
                </div>
              </a>
            @empty
              <p class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-500">No tickets are currently waiting on employee follow-up.</p>
            @endforelse
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-slate-900">Unread Conversations</h3>
            <a href="{{ route('messages.index') }}" class="text-xs font-semibold uppercase tracking-wide text-blue-600 hover:underline">Open inbox</a>
          </div>
          <div class="mt-3 space-y-2">
            @forelse ($managerAttentionQueue['unread_conversations'] as $conversation)
              <a href="{{ route('messages.show', $conversation['id']) }}" class="block rounded-xl border border-blue-200 bg-white px-4 py-3 transition hover:border-blue-300 hover:bg-blue-50">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="font-semibold text-slate-900">{{ $conversation['subject'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $conversation['other_participants'] ?: 'Direct conversation' }}</p>
                  </div>
                  <span class="inline-flex items-center rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-semibold uppercase text-white">
                    {{ $conversation['unread_count'] }} new
                  </span>
                </div>
                <p class="mt-2 text-xs text-slate-500">Updated {{ $conversation['updated_human'] }}</p>
              </a>
            @empty
              <p class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-500">No unread conversations need attention right now.</p>
            @endforelse
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-slate-900">Pending Role Requests</h3>
            <a href="{{ route('role-requests.index') }}" class="text-xs font-semibold uppercase tracking-wide text-blue-600 hover:underline">Open requests</a>
          </div>
          <div class="mt-3 space-y-2">
            @forelse ($managerAttentionQueue['pending_role_requests'] as $request)
              <a href="{{ route('role-requests.index') }}" class="block rounded-xl border border-emerald-200 bg-white px-4 py-3 transition hover:border-emerald-300 hover:bg-emerald-50">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="font-semibold text-slate-900">{{ $request['target_name'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $request['requested_role'] }} · {{ $request['department_name'] ?? 'No department' }}</p>
                  </div>
                  <span class="text-xs text-slate-500">{{ $request['created_human'] }}</span>
                </div>
              </a>
            @empty
              <p class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-500">No pending role requests in your managed departments.</p>
            @endforelse
          </div>
        </div>
      </div>
    </section>
  @endif
@endauth
