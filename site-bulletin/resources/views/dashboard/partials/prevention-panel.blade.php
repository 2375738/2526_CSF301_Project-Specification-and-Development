@php
    $managerPreventionInsights = $managerPreventionInsights ?? null;
    $repeatClusters = collect($managerPreventionInsights['repeat_clusters'] ?? []);
    $breachRisks = collect($managerPreventionInsights['breach_risks'] ?? []);
    $pressure = $managerPreventionInsights['pressure'] ?? null;
@endphp

@auth
  @if (auth()->user()->hasRole('manager', 'ops_manager') && $managerPreventionInsights && $pressure)
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-5">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $managerPreventionInsights['scope_label'] }} · {{ $managerPreventionInsights['window_days'] }} days</p>
          <h2 class="mt-1 text-lg font-semibold text-slate-900">Prevention Watch</h2>
          <p class="mt-1 text-sm text-slate-600">Repeated blockers and aging pressure before they turn into wider shift disruption.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <a href="{{ route('tickets.index') }}" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-blue-300 hover:bg-blue-50">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Open Tickets</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $pressure['open_count'] }}</p>
          </a>
          <a href="{{ route('tickets.index', ['breached' => 1]) }}" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 transition hover:border-rose-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Breach Pressure</p>
            <p class="mt-1 text-2xl font-semibold text-rose-900">{{ $pressure['breached_count'] }}</p>
            <p class="text-xs text-rose-700">{{ number_format((float) $pressure['breach_rate'], 1) }}% of open queue</p>
          </a>
          <a href="{{ route('tickets.index') }}" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 transition hover:border-amber-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Aging 48h+</p>
            <p class="mt-1 text-2xl font-semibold text-amber-900">{{ $pressure['aging_count'] }}</p>
            <p class="text-xs text-amber-700">Oldest {{ $pressure['oldest_age_hours'] }}h</p>
          </a>
          <a href="{{ route('tickets.index', ['status' => 'new']) }}" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 transition hover:border-blue-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Unassigned</p>
            <p class="mt-1 text-2xl font-semibold text-blue-900">{{ $pressure['unassigned_count'] }}</p>
          </a>
        </div>
      </div>

      <div class="grid gap-4 xl:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-slate-900">Repeat Issue Clusters</h3>
            <span class="text-xs text-slate-500">Category · template · location</span>
          </div>
          <div class="mt-3 space-y-2">
            @forelse ($repeatClusters as $cluster)
              <a href="{{ route('tickets.index', $cluster['query']) }}" class="block rounded-xl border border-slate-200 bg-white px-4 py-3 transition hover:border-blue-300 hover:bg-blue-50">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="font-semibold text-slate-900">{{ $cluster['category_name'] }} · {{ $cluster['template_label'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $cluster['department_name'] }} · {{ $cluster['location'] }}</p>
                  </div>
                  <span class="rounded-full bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white">{{ $cluster['total'] }} repeats</span>
                </div>
                <p class="mt-2 text-xs text-slate-500">{{ $cluster['open_count'] }} open · {{ $cluster['breached_count'] }} breached · latest {{ $cluster['latest_human'] }}</p>
              </a>
            @empty
              <p class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-500">No repeated issue clusters in this window.</p>
            @endforelse
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-slate-900">Breach And Aging Risks</h3>
            <a href="{{ route('tickets.index', ['breached' => 1]) }}" class="text-xs font-semibold uppercase tracking-wide text-blue-600 hover:underline">Open breach queue</a>
          </div>
          <div class="mt-3 space-y-2">
            @forelse ($breachRisks as $risk)
              <a href="{{ route('tickets.show', $risk['id']) }}" class="block rounded-xl border border-amber-200 bg-white px-4 py-3 transition hover:border-amber-300 hover:bg-amber-50">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="font-semibold text-slate-900">{{ $risk['title'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $risk['category_name'] }} · {{ $risk['department_name'] }}</p>
                  </div>
                  <span class="text-xs font-semibold {{ $risk['is_breached'] ? 'text-rose-700' : 'text-amber-700' }}">{{ $risk['is_breached'] ? 'Breached' : 'Aging' }}</span>
                </div>
                <p class="mt-2 text-xs text-slate-500">{{ $risk['age_hours'] }}h open</p>
              </a>
            @empty
              <p class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-500">No breached or aging tickets are pressuring the queue.</p>
            @endforelse
          </div>
        </div>
      </div>
    </section>
  @endif
@endauth
