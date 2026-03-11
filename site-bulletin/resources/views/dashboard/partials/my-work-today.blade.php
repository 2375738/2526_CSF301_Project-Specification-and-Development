@php
    $employeeWorkToday = $employeeWorkToday ?? null;
    $focusTickets = collect($employeeWorkToday['focus_tickets'] ?? []);
    $throughputDelta = $employeeWorkToday['throughput_delta'] ?? null;
    $qualityDelta = $employeeWorkToday['quality_delta'] ?? null;
    $throughputTone = $throughputDelta === null ? 'text-slate-500' : ($throughputDelta >= 0 ? 'text-emerald-700' : 'text-rose-700');
    $qualityTone = $qualityDelta === null ? 'text-slate-500' : ($qualityDelta >= 0 ? 'text-emerald-700' : 'text-rose-700');
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
        <div class="dashboard-reveal rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current Throughput</p>
              <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">
                {{ $employeeWorkToday['latest_units_per_hour'] ?? '—' }}
                <span class="text-sm font-normal text-slate-500">/hr</span>
              </p>
              <p class="mt-3 text-sm {{ $throughputTone }}">
                @if ($throughputDelta !== null)
                  {{ $throughputDelta >= 0 ? '+' : '' }}{{ number_format((float) $throughputDelta, 1) }} vs prior hour
                @else
                  Waiting for another comparison window
                @endif
              </p>
            </div>
            <span class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 text-blue-600">
              <svg class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M3.5 13.25a.75.75 0 01.75-.75h2.69l2.1-2.8a.75.75 0 011.13-.08l1.58 1.57 2.72-3.64a.75.75 0 111.2.9l-3.25 4.35a.75.75 0 01-1.12.09L9.66 10.3 7.85 12.7a.75.75 0 01-.6.3H4.25a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                <path fill-rule="evenodd" d="M4.25 4a.75.75 0 000 1.5h11.5a.75.75 0 000-1.5H4.25z" clip-rule="evenodd" opacity="0.45" />
              </svg>
            </span>
          </div>
        </div>
        <div class="dashboard-reveal dashboard-reveal-delay-1 rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Quality Score</p>
              <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">
                {{ $employeeWorkToday['latest_quality_score'] ?? '—' }}%
              </p>
              <p class="mt-3 text-sm {{ $qualityTone }}">
                @if ($qualityDelta !== null)
                  {{ $qualityDelta >= 0 ? '+' : '' }}{{ number_format((float) $qualityDelta, 1) }} vs prior hour
                @else
                  Waiting for another comparison window
                @endif
              </p>
            </div>
            <span class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
              <svg class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 2.5a.75.75 0 01.53.22l4.75 4.75a.75.75 0 01.22.53v3.75a.75.75 0 01-.22.53l-4.75 4.75a.75.75 0 01-1.06 0L4.72 12.28A.75.75 0 014.5 11.75V8a.75.75 0 01.22-.53l4.75-4.75A.75.75 0 0110 2.5zm0 2.34L6 8.84v2.91l4 4 4-4V8.84l-4-4z" clip-rule="evenodd" />
                <path fill-rule="evenodd" d="M10 6.75a.75.75 0 01.75.75v1.94h1.94a.75.75 0 010 1.5h-1.94v1.94a.75.75 0 01-1.5 0v-1.94H7.31a.75.75 0 010-1.5h1.94V7.5a.75.75 0 01.75-.75z" clip-rule="evenodd" />
              </svg>
            </span>
          </div>
        </div>
        <div class="dashboard-reveal dashboard-reveal-delay-2 rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Unread Updates</p>
              <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $employeeWorkToday['unread_announcement_count'] }}</p>
              <p class="mt-3 text-sm text-slate-500">{{ $employeeWorkToday['unread_message_count'] }} unread conversation{{ $employeeWorkToday['unread_message_count'] === 1 ? '' : 's' }}</p>
            </div>
            <span class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-600">
              <svg class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M3.75 4A1.75 1.75 0 002 5.75v7.5C2 14.22 2.78 15 3.75 15h2.54l2.97 2.44a.75.75 0 001.24-.58V15h5.75A1.75 1.75 0 0018 13.25v-7.5A1.75 1.75 0 0016.25 4H3.75zm1.5 3a.75.75 0 000 1.5h9.5a.75.75 0 000-1.5h-9.5zm0 3a.75.75 0 000 1.5h6a.75.75 0 000-1.5h-6z" clip-rule="evenodd" />
              </svg>
            </span>
          </div>
        </div>
        <div class="dashboard-reveal dashboard-reveal-delay-3 rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Open Tickets</p>
              <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $employeeWorkToday['open_ticket_count'] }}</p>
              <p class="mt-3 text-sm text-slate-500">
                {{ $employeeWorkToday['action_required_count'] }} need your reply · {{ $employeeWorkToday['breached_ticket_count'] }} breached
              </p>
            </div>
            <span class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
              <svg class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M4.75 3A1.75 1.75 0 003 4.75v10.5C3 16.22 3.78 17 4.75 17h10.5A1.75 1.75 0 0017 15.25V7.81a.75.75 0 00-.22-.53l-4.06-4.06A.75.75 0 0012.19 3H4.75zm6.5 1.81L14.19 7.75h-2.19a.75.75 0 01-.75-.75V4.81zM6.5 10.25a.75.75 0 01.75-.75h5.5a.75.75 0 010 1.5h-5.5a.75.75 0 01-.75-.75zm0 3a.75.75 0 01.75-.75h3.5a.75.75 0 010 1.5h-3.5a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
              </svg>
            </span>
          </div>
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
