@php
    $employeeWorkToday = $employeeWorkToday ?? null;
    $focusTickets = collect($employeeWorkToday['focus_tickets'] ?? []);
    $throughputDelta = $employeeWorkToday['throughput_delta'] ?? null;
    $rankDelta = $employeeWorkToday['rank_delta'] ?? null;
    $throughputTone = $throughputDelta === null ? 'text-slate-500' : ($throughputDelta >= 0 ? 'text-emerald-700' : 'text-rose-700');
    $rankTone = $rankDelta === null ? 'text-slate-500' : ($rankDelta >= 0 ? 'text-emerald-700' : 'text-rose-700');
@endphp

@auth
  @if (auth()->user()->isEmployee() && $employeeWorkToday)
    <section class="rounded-3xl border border-blue-200 bg-gradient-to-br from-blue-50 via-white to-emerald-50 p-6 shadow-sm space-y-6">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="max-w-2xl">
          <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-700">My Work Today</p>
          <h2 class="mt-2 text-2xl font-semibold text-slate-900">
            {{ $employeeWorkToday['shift_name'] }} in {{ $employeeWorkToday['department_name'] }}
          </h2>
          <p class="mt-2 text-sm text-slate-600">
            {{ $employeeWorkToday['department_code'] }} ·
            {{ optional($employeeWorkToday['shift_start'] ?? null)->format('H:i') }}-{{ optional($employeeWorkToday['shift_end'] ?? null)->format('H:i') }}
            @if (! empty($employeeWorkToday['manager_name']))
              · Manager: {{ $employeeWorkToday['manager_name'] }}
            @endif
          </p>
          @if (! empty($employeeWorkToday['department_description']))
            <p class="mt-2 text-sm text-slate-600">{{ $employeeWorkToday['department_description'] }}</p>
          @endif
        </div>
        <div class="flex flex-wrap gap-3">
          <a href="{{ route('tickets.create') }}" class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            Report an Issue
          </a>
          <a href="{{ route('tickets.index') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            View My Tickets
          </a>
          <a href="{{ route('messages.index') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Open Messages
          </a>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current Throughput</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">
            {{ $employeeWorkToday['latest_units_per_hour'] ?? '—' }}
            <span class="text-xs font-normal text-slate-500">units/hr</span>
          </p>
          <p class="mt-1 text-xs {{ $throughputTone }}">
            @if ($throughputDelta !== null)
              {{ $throughputDelta >= 0 ? '+' : '' }}{{ number_format((float) $throughputDelta, 1) }} vs previous snapshot
            @else
              No prior snapshot available
            @endif
          </p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Quality Position</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">
            {{ $employeeWorkToday['latest_rank_percentile'] ?? '—' }}%
          </p>
          <p class="mt-1 text-xs {{ $rankTone }}">
            @if ($rankDelta !== null)
              {{ $rankDelta >= 0 ? '+' : '' }}{{ $rankDelta }} improvement vs previous snapshot
            @else
              Waiting for another comparison point
            @endif
          </p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Unread Updates</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $employeeWorkToday['unread_announcement_count'] }}</p>
          <p class="mt-1 text-xs text-slate-500">{{ $employeeWorkToday['unread_message_count'] }} unread conversation{{ $employeeWorkToday['unread_message_count'] === 1 ? '' : 's' }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Open Tickets</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $employeeWorkToday['open_ticket_count'] }}</p>
          <p class="mt-1 text-xs text-slate-500">
            {{ $employeeWorkToday['action_required_count'] }} need your reply · {{ $employeeWorkToday['breached_ticket_count'] }} breached
          </p>
        </div>
      </div>

      <div class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
          <div class="flex items-center justify-between">
            <div>
            <h3 class="text-base font-semibold text-slate-900">Today's Focus</h3>
              <p class="text-xs text-slate-500">The quickest way to see if you are blocked or waiting on an update.</p>
            </div>
            <a href="{{ route('tickets.index') }}" class="text-xs font-semibold uppercase tracking-wide text-blue-600 hover:underline">All tickets</a>
          </div>

          @if ($focusTickets->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">
              No open tickets right now. Use the quick action above if you need to raise a new issue.
            </div>
          @else
            <div class="space-y-3">
              @foreach ($focusTickets as $ticket)
                <a href="{{ route('tickets.show', $ticket['id']) }}" class="block rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 transition hover:border-blue-200 hover:bg-blue-50">
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-slate-900 px-2.5 py-0.5 text-[10px] font-semibold uppercase text-white">{{ $ticket['status_label'] }}</span>
                    <span class="inline-flex items-center rounded-full bg-white px-2.5 py-0.5 text-[10px] font-semibold uppercase text-slate-700 ring-1 ring-inset ring-slate-200">{{ $ticket['priority_label'] }}</span>
                    @if ($ticket['requires_action'])
                      <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase text-amber-700">Your reply needed</span>
                    @endif
                    @if ($ticket['is_breached'])
                      <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase text-rose-700">Breached</span>
                    @endif
                  </div>
                  <h4 class="mt-3 text-sm font-semibold text-slate-900">#{{ $ticket['id'] }} {{ $ticket['title'] }}</h4>
                  <p class="mt-1 text-sm text-slate-600">{{ $ticket['next_step'] }}</p>
                  <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                    <span>{{ $ticket['category_name'] }}</span>
                    <span>Updated {{ $ticket['updated_human'] }}</span>
                    @if (! empty($ticket['assignee_name']))
                      <span>Owner: {{ $ticket['assignee_name'] }}</span>
                    @else
                      <span>Awaiting assignment</span>
                    @endif
                  </div>
                </a>
              @endforeach
            </div>
          @endif
        </div>

        <div class="space-y-4">
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Shift Targets</h3>
            <div class="mt-4 space-y-3 text-sm">
              <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                <span class="text-slate-600">Target throughput</span>
                <span class="font-semibold text-slate-900">{{ number_format((float) $employeeWorkToday['target_units_per_hour'], 1) }} /hr</span>
              </div>
              <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                <span class="text-slate-600">Target quality</span>
                <span class="font-semibold text-slate-900">{{ number_format((float) $employeeWorkToday['target_quality_pct'], 1) }}%</span>
              </div>
            </div>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Attention Needed</h3>
            <div class="mt-4 space-y-3 text-sm">
              <div class="flex items-center justify-between rounded-xl border px-4 py-3 {{ $employeeWorkToday['action_required_count'] > 0 ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-slate-50' }}">
                <span class="text-slate-700">Waiting on you</span>
                <span class="font-semibold text-slate-900">{{ $employeeWorkToday['action_required_count'] }}</span>
              </div>
              <div class="flex items-center justify-between rounded-xl border px-4 py-3 {{ $employeeWorkToday['breached_ticket_count'] > 0 ? 'border-rose-200 bg-rose-50' : 'border-slate-200 bg-slate-50' }}">
                <span class="text-slate-700">Breached tickets</span>
                <span class="font-semibold text-slate-900">{{ $employeeWorkToday['breached_ticket_count'] }}</span>
              </div>
              <div class="flex items-center justify-between rounded-xl border px-4 py-3 {{ $employeeWorkToday['unassigned_ticket_count'] > 0 ? 'border-blue-200 bg-blue-50' : 'border-slate-200 bg-slate-50' }}">
                <span class="text-slate-700">Awaiting assignment</span>
                <span class="font-semibold text-slate-900">{{ $employeeWorkToday['unassigned_ticket_count'] }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  @endif
@endauth
