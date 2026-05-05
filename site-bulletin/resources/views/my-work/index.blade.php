@extends('layouts.app')

@section('content')
  @php
      $snapshots = $snapshots instanceof \Illuminate\Support\Collection ? $snapshots : collect($snapshots ?? []);
      $employeeOverview = $employeeOverview ?? null;
      $employeeWorkToday = $employeeWorkToday ?? null;
      $activeSeries = collect($employeeOverview['series_by_scale'][$employeeOverview['active_scale'] ?? '7d'] ?? []);
      $latestPoint = $activeSeries->last();
      $currentThroughput = $employeeWorkToday['latest_units_per_hour'] ?? $latestPoint['productivity'] ?? null;
      $currentQuality = $employeeWorkToday['latest_quality_score'] ?? $latestPoint['quality'] ?? null;
      $targetThroughput = (float) ($employeeOverview['target_productivity'] ?? ($employeeWorkToday['target_units_per_hour'] ?? 42));
      $targetQuality = (float) ($employeeOverview['target_quality'] ?? ($employeeWorkToday['target_quality_pct'] ?? 95));
      $averageThroughput = $activeSeries->isNotEmpty() ? round((float) $activeSeries->avg('productivity'), 1) : null;
      $averageQuality = $activeSeries->isNotEmpty() ? round((float) $activeSeries->avg('quality'), 1) : null;
      $bestThroughput = $activeSeries->isNotEmpty() ? round((float) $activeSeries->max('productivity'), 1) : null;
      $bestQuality = $activeSeries->isNotEmpty() ? round((float) $activeSeries->max('quality'), 1) : null;
      $throughputGap = $currentThroughput !== null ? round((float) $currentThroughput - $targetThroughput, 1) : null;
      $qualityGap = $currentQuality !== null ? round((float) $currentQuality - $targetQuality, 1) : null;

      $productivityStatus = match (true) {
          $throughputGap === null => 'neutral',
          $throughputGap >= 0 => 'green',
          $throughputGap >= -2 => 'amber',
          default => 'red',
      };

      $qualityStatus = match (true) {
          $qualityGap === null => 'neutral',
          $qualityGap >= 0 => 'green',
          $qualityGap >= -1 => 'amber',
          default => 'red',
      };

      $productivityCardClass = match ($productivityStatus) {
          'green' => 'border-emerald-200 bg-emerald-50',
          'amber' => 'border-amber-200 bg-amber-50',
          'red' => 'border-rose-200 bg-rose-50',
          default => 'border-slate-200 bg-white',
      };

      $productivityLabelClass = match ($productivityStatus) {
          'green' => 'text-emerald-700',
          'amber' => 'text-amber-700',
          'red' => 'text-rose-700',
          default => 'text-slate-500',
      };

      $productivityValueClass = match ($productivityStatus) {
          'green' => 'text-emerald-950',
          'amber' => 'text-amber-950',
          'red' => 'text-rose-950',
          default => 'text-slate-900',
      };

      $qualityCardClass = match ($qualityStatus) {
          'green' => 'border-emerald-200 bg-emerald-50',
          'amber' => 'border-amber-200 bg-amber-50',
          'red' => 'border-rose-200 bg-rose-50',
          default => 'border-slate-200 bg-white',
      };

      $qualityLabelClass = match ($qualityStatus) {
          'green' => 'text-emerald-700',
          'amber' => 'text-amber-700',
          'red' => 'text-rose-700',
          default => 'text-slate-500',
      };

      $qualityValueClass = match ($qualityStatus) {
          'green' => 'text-emerald-950',
          'amber' => 'text-amber-950',
          'red' => 'text-rose-950',
          default => 'text-slate-900',
      };
  @endphp

  <div class="space-y-8">
    <section class="rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 px-6 py-6 text-white shadow-lg">
      <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-3xl">
          <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-blue-100">My Work</p>
          <h1 class="mt-2 text-3xl font-semibold tracking-tight">Performance and quality detail</h1>
          <p class="mt-3 text-sm text-slate-200">
            Use this view to track your current productivity, quality against target, and recent trend windows from the same 15-minute sample data that powers the rest of the app.
          </p>
        </div>
        <div class="flex flex-wrap gap-3">
          <a href="{{ route('home') }}" class="inline-flex items-center rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white hover:text-slate-900">
            Back to Dashboard
          </a>
          <a href="{{ route('tickets.index') }}" class="inline-flex items-center rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white hover:text-slate-900">
            View My Tickets
          </a>
        </div>
      </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <div id="productivity-detail" class="rounded-2xl border px-5 py-5 shadow-sm {{ $productivityCardClass }}">
        <p class="text-xs font-semibold uppercase tracking-wide {{ $productivityLabelClass }}">Current Productivity</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight {{ $productivityValueClass }}">
          {{ $currentThroughput !== null ? number_format((float) $currentThroughput, 1) : '—' }}
          <span class="text-sm font-normal text-slate-500">/hr</span>
        </p>
        <p class="mt-3 text-sm {{ $productivityLabelClass }}">
          @if ($throughputGap !== null)
            {{ $throughputGap >= 0 ? '+' : '' }}{{ number_format((float) $throughputGap, 1) }} vs target {{ number_format($targetThroughput, 1) }}/hr
          @else
            Target comparison unavailable
          @endif
        </p>
      </div>
      <div id="quality-detail" class="rounded-2xl border px-5 py-5 shadow-sm {{ $qualityCardClass }}">
        <p class="text-xs font-semibold uppercase tracking-wide {{ $qualityLabelClass }}">Current Quality</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight {{ $qualityValueClass }}">
          {{ $currentQuality !== null ? number_format((float) $currentQuality, 1) : '—' }}%
        </p>
        <p class="mt-3 text-sm {{ $qualityLabelClass }}">
          @if ($qualityGap !== null)
            {{ $qualityGap >= 0 ? '+' : '' }}{{ number_format((float) $qualityGap, 1) }} vs target {{ number_format($targetQuality, 1) }}%
          @else
            Target comparison unavailable
          @endif
        </p>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Window Average</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">
          {{ $averageThroughput !== null ? number_format((float) $averageThroughput, 1) : '—' }}
          <span class="text-sm font-normal text-slate-500">/hr</span>
        </p>
        <p class="mt-3 text-sm text-slate-500">
          Quality average {{ $averageQuality !== null ? number_format((float) $averageQuality, 1) . '%' : '—' }} across the selected chart window
        </p>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Best Window</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">
          {{ $bestThroughput !== null ? number_format((float) $bestThroughput, 1) : '—' }}
          <span class="text-sm font-normal text-slate-500">/hr</span>
        </p>
        <p class="mt-3 text-sm text-slate-500">
          Best quality in window {{ $bestQuality !== null ? number_format((float) $bestQuality, 1) . '%' : '—' }}
        </p>
      </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1.4fr_0.6fr]">
      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        @include('dashboard.partials.trend-panels', [
            'snapshots' => $snapshots,
            'departmentMetricTrend' => collect(),
            'showEmployeeTrend' => true,
        ])
      </div>

      <div class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-base font-semibold text-slate-900">How to use this view</h2>
          <div class="mt-4 space-y-3 text-sm text-slate-600">
            <p>Use `Last 3 hours` to spot short-term changes in pace and quality.</p>
            <p>Use `Last 24 hours` to compare how stable your shift output has been across the day.</p>
            <p>Use `Last 7 days` for broader trend reading rather than immediate action.</p>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-base font-semibold text-slate-900">Current target check</h2>
          <div class="mt-4 space-y-3 text-sm">
            <div class="rounded-xl bg-slate-50 px-4 py-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Productivity target</p>
              <p class="mt-1 font-semibold text-slate-900">{{ number_format($targetThroughput, 1) }}/hr</p>
            </div>
            <div class="rounded-xl bg-slate-50 px-4 py-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Quality target</p>
              <p class="mt-1 font-semibold text-slate-900">{{ number_format($targetQuality, 1) }}%</p>
            </div>
            @if ($employeeWorkToday)
              <div class="rounded-xl bg-slate-50 px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Open work items</p>
                <p class="mt-1 font-semibold text-slate-900">{{ $employeeWorkToday['open_ticket_count'] }} tickets · {{ $employeeWorkToday['unread_message_count'] }} unread conversations</p>
              </div>
            @endif
          </div>
        </div>
      </div>
    </section>
  </div>
@endsection
