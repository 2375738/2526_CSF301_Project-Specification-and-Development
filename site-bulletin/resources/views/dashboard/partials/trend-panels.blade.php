@php
    $employeeTrend = ($snapshots ?? collect())
        ->sortBy('week_start')
        ->values();

    $employeeMax = max(1, (int) $employeeTrend->max('units_per_hour'));
    $employeeBestRank = $employeeTrend->min('rank_percentile');
    $employeeLatestRank = $employeeTrend->last()?->rank_percentile;
    $employeeOverview = $employeeOverview ?? null;

    $managerTrend = ($departmentMetricTrend ?? collect())->values();
    $managerMaxOpen = max(1, (int) $managerTrend->max('open_tickets'));
    $managerBenchmark = $managerBenchmark ?? null;
    $managerOverview = $managerOverview ?? null;
    $managerHealthSummary = $managerHealthSummary ?? null;
    $managerSlaHealth = $managerSlaHealth ?? null;
    $managerSlaTimeline = ($managerSlaTimeline ?? collect())->values();
    $managerTicketTypeBreakdown = $managerTicketTypeBreakdown ?? collect();
    $managerTrendWindow = $managerTrendWindow ?? null;

    $drilldownSeries = $managerSlaTimeline->isNotEmpty()
        ? $managerSlaTimeline
        : $managerTrend->map(fn ($point) => [
            'metric_date' => $point->metric_date,
            'total_tickets' => (int) ($point->open_tickets ?? 0),
            'breached_tickets' => (int) ($point->sla_breaches ?? 0),
            'within_tickets' => max(0, (int) ($point->open_tickets ?? 0) - (int) ($point->sla_breaches ?? 0)),
            'open_tickets' => (int) ($point->open_tickets ?? 0),
        ])->values();

    $managerMaxBreaches = max(1, (int) $drilldownSeries->max('breached_tickets'));
    $breachNonZeroCount = $drilldownSeries->filter(fn ($point) => (int) ($point['breached_tickets'] ?? 0) > 0)->count();
    $breachTotal = (int) $drilldownSeries->sum('breached_tickets');
    $useCompactDrilldown = $breachNonZeroCount <= 1;
@endphp

@auth
  @if (auth()->user()->isEmployee() && $employeeTrend->isNotEmpty())
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
      <header class="flex items-center justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-900">Employee Performance Trend</h2>
          <p class="text-xs text-slate-500">Productivity and quality charts with the same interaction model as the manager view.</p>
        </div>
      </header>
      @if ($employeeOverview && collect($employeeOverview['series_by_scale'] ?? [])->flatten(1)->isNotEmpty())
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-4 space-y-4" x-data="{ activeScale: '{{ $employeeOverview['active_scale'] ?? '7d' }}' }">
          <div class="flex items-center justify-between px-1">
            <div>
              <h3 class="text-sm font-semibold text-slate-900">Performance Trend</h3>
              <p class="text-[11px] text-slate-500">Quality vs Productivity Trend across the selected window.</p>
            </div>
            <div class="inline-flex rounded-lg border border-slate-300 bg-white p-1 text-xs">
              @foreach (['7d' => 'Last 7 days', '24h' => 'Last 24 hours', '3h' => 'Last 3 hours'] as $scaleKey => $scaleLabel)
                <button
                  type="button"
                  x-on:click="activeScale = '{{ $scaleKey }}'"
                  x-bind:class="activeScale === '{{ $scaleKey }}' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
                  class="rounded-md px-2 py-1"
                >
                  {{ $scaleLabel }}
                </button>
              @endforeach
            </div>
          </div>

          @foreach (['7d', '24h', '3h'] as $employeeScaleKey)
            @php
              $employeeSeries = collect($employeeOverview['series_by_scale'][$employeeScaleKey] ?? [])->values();
              $employeeTargetProductivity = (float) ($employeeOverview['target_productivity'] ?? 42);
              $employeeTargetQuality = (float) ($employeeOverview['target_quality'] ?? 95);
              $employeeMaxProd = max(55.0, (float) $employeeSeries->max('productivity') + 8.0, $employeeTargetProductivity + 8.0);
              $employeeMinProd = max(0.0, min((float) floor(($employeeSeries->min('productivity') ?? 0) - 8.0), $employeeTargetProductivity - 10.0));
              $employeeMaxQuality = 100.0;
              $employeeMinQuality = max(0.0, min((float) floor(($employeeSeries->min('quality') ?? 80) - 8.0), $employeeTargetQuality - 12.0));
              $employeeWidth = 640;
              $employeeHeight = 230;
              $employeePaddingX = 42;
              $employeePaddingTop = 18;
              $employeePaddingBottom = 26;
              $employeePlotWidth = $employeeWidth - ($employeePaddingX * 2);
              $employeePlotHeight = $employeeHeight - ($employeePaddingTop + $employeePaddingBottom);
              $employeeCount = max(1, $employeeSeries->count());
              $employeeStepX = $employeeCount > 1 ? ($employeePlotWidth / ($employeeCount - 1)) : 0;

              $employeeCoords = function ($key, $minY, $maxY) use ($employeeSeries, $employeePaddingX, $employeePaddingTop, $employeePlotHeight, $employeeStepX) {
                  $range = max(1, $maxY - $minY);
                  return $employeeSeries->values()->map(function ($point, $index) use ($key, $range, $minY, $employeePaddingX, $employeePaddingTop, $employeePlotHeight, $employeeStepX) {
                      $x = $employeePaddingX + ($index * $employeeStepX);
                      $normalized = ((float) $point[$key] - $minY) / $range;
                      $y = $employeePaddingTop + ($employeePlotHeight - ($normalized * $employeePlotHeight));
                      return number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');
                  })->implode(' ');
              };

              $employeeProdRange = max(1, $employeeMaxProd - $employeeMinProd);
              $employeeQualityRange = max(1, $employeeMaxQuality - $employeeMinQuality);
              $employeeProdTargetY = $employeePaddingTop + ($employeePlotHeight - ((($employeeTargetProductivity - $employeeMinProd) / $employeeProdRange) * $employeePlotHeight));
              $employeeQualityTargetY = $employeePaddingTop + ($employeePlotHeight - ((($employeeTargetQuality - $employeeMinQuality) / $employeeQualityRange) * $employeePlotHeight));
              $employeePeakProductivity = round((float) $employeeSeries->max('productivity'), 1);
              $employeeLatestQuality = round((float) ($employeeSeries->last()['quality'] ?? 0), 1);
              $employeeBestQuality = round((float) $employeeSeries->max('quality'), 1);
            @endphp
          <div x-cloak x-show="activeScale === '{{ $employeeScaleKey }}'" class="space-y-4">
          <div class="grid gap-4 lg:grid-cols-2">
            <div
              class="rounded-lg border border-slate-200 bg-white p-3"
              x-data="{ active: null, setActive(event, payload) { const rect = this.$el.getBoundingClientRect(); const tooltipWidth = 164; const edgePad = 10; let left = event.clientX - rect.left; const minLeft = edgePad + (tooltipWidth / 2); const maxLeft = rect.width - edgePad - (tooltipWidth / 2); if (rect.width <= (tooltipWidth + (edgePad * 2))) { left = rect.width / 2; } else { left = Math.min(Math.max(left, minLeft), maxLeft); } this.active = { ...payload, left, top: Math.max(18, event.clientY - rect.top - 10) }; } }"
              x-on:mouseleave="active = null"
            >
              <div class="mb-2 flex items-center justify-between">
                <p class="text-sm font-semibold text-blue-700">Productivity (units/hr)</p>
                <p class="text-xs text-slate-500">Target: {{ number_format((float) ($employeeOverview['target_productivity'] ?? 42), 1) }}</p>
              </div>
              <div class="relative">
                <svg viewBox="0 0 {{ $employeeWidth }} {{ $employeeHeight }}" class="w-full">
                  @foreach ([0, 0.25, 0.5, 0.75, 1] as $gridStep)
                    @php
                      $gridY = $employeePaddingTop + ($employeePlotHeight * $gridStep);
                      $tickValue = number_format($employeeMaxProd - (($employeeMaxProd - $employeeMinProd) * $gridStep), 1);
                    @endphp
                    <line x1="{{ $employeePaddingX }}" y1="{{ number_format($gridY, 2, '.', '') }}" x2="{{ $employeePaddingX + $employeePlotWidth }}" y2="{{ number_format($gridY, 2, '.', '') }}" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="3 3" />
                    <text x="{{ $employeePaddingX - 32 }}" y="{{ number_format($gridY + 3, 2, '.', '') }}" fill="#64748b" font-size="10">{{ $tickValue }}</text>
                  @endforeach
                  <line x1="{{ $employeePaddingX }}" y1="{{ $employeePaddingTop }}" x2="{{ $employeePaddingX }}" y2="{{ $employeePaddingTop + $employeePlotHeight }}" stroke="#cbd5e1" stroke-width="1" />
                  <line x1="{{ $employeePaddingX }}" y1="{{ $employeePaddingTop + $employeePlotHeight }}" x2="{{ $employeePaddingX + $employeePlotWidth }}" y2="{{ $employeePaddingTop + $employeePlotHeight }}" stroke="#cbd5e1" stroke-width="1" />
                  <line x1="{{ $employeePaddingX }}" y1="{{ number_format($employeeProdTargetY, 2, '.', '') }}" x2="{{ $employeePaddingX + $employeePlotWidth }}" y2="{{ number_format($employeeProdTargetY, 2, '.', '') }}" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4 4" />
                  <text x="{{ $employeePaddingX + 6 }}" y="{{ number_format($employeeProdTargetY - 4, 2, '.', '') }}" fill="#b45309" font-size="10">Target</text>
                  <polyline points="{{ $employeeCoords('productivity', $employeeMinProd, $employeeMaxProd) }}" fill="none" stroke="#2563eb" stroke-width="2.2" class="chart-line-animate" />
                  @foreach ($employeeSeries as $index => $point)
                    @php
                      $x = $employeePaddingX + ($index * $employeeStepX);
                      $y = $employeePaddingTop + ($employeePlotHeight - (((($point['productivity'] ?? 0) - $employeeMinProd) / $employeeProdRange) * $employeePlotHeight));
                      $hoverWidth = $employeeCount > 1 ? max(18, ($employeePlotWidth / $employeeCount)) : $employeePlotWidth;
                      $hoverX = $employeeCount > 1 ? ($x - ($hoverWidth / 2)) : $employeePaddingX;
                    @endphp
                    <rect x="{{ number_format($hoverX, 2, '.', '') }}" y="{{ number_format($employeePaddingTop, 2, '.', '') }}" width="{{ number_format($hoverWidth, 2, '.', '') }}" height="{{ number_format($employeePlotHeight, 2, '.', '') }}" fill="transparent"
                      x-on:mouseenter="setActive($event, {label: '{{ $point['label'] }}', value: '{{ number_format((float) $point['productivity'], 1) }}', unit: 'units/hr'})"
                      x-on:mousemove="setActive($event, {label: '{{ $point['label'] }}', value: '{{ number_format((float) $point['productivity'], 1) }}', unit: 'units/hr'})" />
                    <circle cx="{{ number_format($x, 2, '.', '') }}" cy="{{ number_format($y, 2, '.', '') }}" r="3" fill="#2563eb" class="chart-point-animate" style="animation-delay: {{ number_format($index * 0.02, 2, '.', '') }}s"></circle>
                    <text x="{{ number_format($x - 12, 2, '.', '') }}" y="{{ $employeeHeight - 6 }}" fill="#64748b" font-size="10">{{ $point['label'] }}</text>
                  @endforeach
                </svg>
                <div x-show="active" x-cloak class="pointer-events-none absolute z-20 w-40 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs shadow" :style="`left: ${active.left}px; top: ${active.top}px; transform: translate(-50%, -100%);`">
                  <p class="font-semibold text-slate-900" x-text="active?.label"></p>
                  <p class="text-blue-700"><span x-text="active?.value"></span> <span x-text="active?.unit"></span></p>
                </div>
              </div>
            </div>

            <div
              class="rounded-lg border border-slate-200 bg-white p-3"
              x-data="{ active: null, setActive(event, payload) { const rect = this.$el.getBoundingClientRect(); const tooltipWidth = 164; const edgePad = 10; let left = event.clientX - rect.left; const minLeft = edgePad + (tooltipWidth / 2); const maxLeft = rect.width - edgePad - (tooltipWidth / 2); if (rect.width <= (tooltipWidth + (edgePad * 2))) { left = rect.width / 2; } else { left = Math.min(Math.max(left, minLeft), maxLeft); } this.active = { ...payload, left, top: Math.max(18, event.clientY - rect.top - 10) }; } }"
              x-on:mouseleave="active = null"
            >
              <div class="mb-2 flex items-center justify-between">
                <p class="text-sm font-semibold text-emerald-700">Quality (%)</p>
                <p class="text-xs text-slate-500">Target: {{ number_format((float) ($employeeOverview['target_quality'] ?? 95), 1) }}</p>
              </div>
              <div class="relative">
                <svg viewBox="0 0 {{ $employeeWidth }} {{ $employeeHeight }}" class="w-full">
                  @foreach ([0, 0.25, 0.5, 0.75, 1] as $gridStep)
                    @php
                      $gridY = $employeePaddingTop + ($employeePlotHeight * $gridStep);
                      $tickValue = number_format($employeeMaxQuality - (($employeeMaxQuality - $employeeMinQuality) * $gridStep), 1);
                    @endphp
                    <line x1="{{ $employeePaddingX }}" y1="{{ number_format($gridY, 2, '.', '') }}" x2="{{ $employeePaddingX + $employeePlotWidth }}" y2="{{ number_format($gridY, 2, '.', '') }}" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="3 3" />
                    <text x="{{ $employeePaddingX - 32 }}" y="{{ number_format($gridY + 3, 2, '.', '') }}" fill="#64748b" font-size="10">{{ $tickValue }}</text>
                  @endforeach
                  <line x1="{{ $employeePaddingX }}" y1="{{ $employeePaddingTop }}" x2="{{ $employeePaddingX }}" y2="{{ $employeePaddingTop + $employeePlotHeight }}" stroke="#cbd5e1" stroke-width="1" />
                  <line x1="{{ $employeePaddingX }}" y1="{{ $employeePaddingTop + $employeePlotHeight }}" x2="{{ $employeePaddingX + $employeePlotWidth }}" y2="{{ $employeePaddingTop + $employeePlotHeight }}" stroke="#cbd5e1" stroke-width="1" />
                  <line x1="{{ $employeePaddingX }}" y1="{{ number_format($employeeQualityTargetY, 2, '.', '') }}" x2="{{ $employeePaddingX + $employeePlotWidth }}" y2="{{ number_format($employeeQualityTargetY, 2, '.', '') }}" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4 4" />
                  <text x="{{ $employeePaddingX + 6 }}" y="{{ number_format($employeeQualityTargetY - 4, 2, '.', '') }}" fill="#b45309" font-size="10">Target</text>
                  <polyline points="{{ $employeeCoords('quality', $employeeMinQuality, $employeeMaxQuality) }}" fill="none" stroke="#10b981" stroke-width="2.2" style="stroke-dasharray:1000;stroke-dashoffset:1000;animation:chart-draw 1.2s ease .15s forwards;" />
                  @foreach ($employeeSeries as $index => $point)
                    @php
                      $x = $employeePaddingX + ($index * $employeeStepX);
                      $y = $employeePaddingTop + ($employeePlotHeight - (((($point['quality'] ?? 0) - $employeeMinQuality) / $employeeQualityRange) * $employeePlotHeight));
                      $hoverWidth = $employeeCount > 1 ? max(18, ($employeePlotWidth / $employeeCount)) : $employeePlotWidth;
                      $hoverX = $employeeCount > 1 ? ($x - ($hoverWidth / 2)) : $employeePaddingX;
                    @endphp
                    <rect x="{{ number_format($hoverX, 2, '.', '') }}" y="{{ number_format($employeePaddingTop, 2, '.', '') }}" width="{{ number_format($hoverWidth, 2, '.', '') }}" height="{{ number_format($employeePlotHeight, 2, '.', '') }}" fill="transparent"
                      x-on:mouseenter="setActive($event, {label: '{{ $point['label'] }}', value: '{{ number_format((float) $point['quality'], 1) }}', unit: '%'})"
                      x-on:mousemove="setActive($event, {label: '{{ $point['label'] }}', value: '{{ number_format((float) $point['quality'], 1) }}', unit: '%'})" />
                    <circle cx="{{ number_format($x, 2, '.', '') }}" cy="{{ number_format($y, 2, '.', '') }}" r="3" fill="#10b981" class="chart-point-animate" style="animation-delay: {{ number_format(($index * 0.02) + 0.08, 2, '.', '') }}s"></circle>
                    <text x="{{ number_format($x - 12, 2, '.', '') }}" y="{{ $employeeHeight - 6 }}" fill="#64748b" font-size="10">{{ $point['label'] }}</text>
                  @endforeach
                </svg>
                <div x-show="active" x-cloak class="pointer-events-none absolute z-20 w-40 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs shadow" :style="`left: ${active.left}px; top: ${active.top}px; transform: translate(-50%, -100%);`">
                  <p class="font-semibold text-slate-900" x-text="active?.label"></p>
                  <p class="text-emerald-700"><span x-text="active?.value"></span><span x-text="active?.unit"></span></p>
                </div>
              </div>
            </div>
          </div>
          <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3">
          <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Peak Throughput</p>
          <p class="mt-1 text-2xl font-semibold text-blue-900">{{ number_format((float) $employeePeakProductivity, 1) }} /hr</p>
          <p class="text-xs text-blue-700">Highest point in selected trend window</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
          <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Rank Trend</p>
          <p class="mt-1 text-2xl font-semibold text-emerald-900">
            {{ number_format((float) $employeeLatestQuality, 1) }}%
          </p>
          <p class="text-xs text-emerald-700">
            Quality view. Best in window: {{ number_format((float) $employeeBestQuality, 1) }}%
          </p>
        </div>
      </div>
          </div>
          @endforeach
        </div>
      @endif
    </section>
  @endif

  @if (auth()->user()->hasRole('manager', 'ops_manager') && $managerTrend->isNotEmpty())
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
      @if ($managerOverview)
        @php
          $productivityBandClass = match ($managerOverview['productivity_status'] ?? 'amber') {
              'green' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
              'red' => 'border-rose-200 bg-rose-50 text-rose-900',
              default => 'border-amber-200 bg-amber-50 text-amber-900',
          };

          $qualityBandClass = match ($managerOverview['quality_status'] ?? 'amber') {
              'green' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
              'red' => 'border-rose-200 bg-rose-50 text-rose-900',
              default => 'border-amber-200 bg-amber-50 text-amber-900',
          };
        @endphp
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
            <p class="text-xs text-slate-500">Team Size</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $managerOverview['team_size'] }}</p>
            <p class="text-xs text-slate-500">
              Active now / {{ $managerOverview['planned_team_size'] }} planned · {{ $managerOverview['shift_name'] }}
            </p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
            <p class="text-xs text-slate-500">Units Processed (Current Shift)</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format((int) $managerOverview['units_processed']) }}</p>
            <p class="text-xs text-slate-600">
              Target {{ number_format((int) ($managerOverview['shift_target_units'] ?? 0)) }} ·
              {{ optional($managerOverview['shift_start'] ?? null)->format('H:i') }}-{{ optional($managerOverview['shift_end'] ?? null)->format('H:i') }}
            </p>
          </div>
          <div class="rounded-xl border px-4 py-3 {{ $productivityBandClass }}">
            <p class="text-xs text-slate-500">Avg Productivity</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format((float) $managerOverview['avg_productivity'], 1) }} /hr</p>
            <p class="text-xs">{{ number_format((float) ($managerOverview['productivity_achievement_pct'] ?? 0), 1) }}% vs target</p>
          </div>
          <div class="rounded-xl border px-4 py-3 {{ $qualityBandClass }}">
            <p class="text-xs text-slate-500">Quality Score</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format((float) $managerOverview['quality_score'], 1) }}%</p>
            <p class="text-xs">{{ number_format((float) ($managerOverview['quality_achievement_pct'] ?? 0), 1) }}% vs target</p>
          </div>
        </div>
      @endif

      <header class="flex items-center justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-900">Department Trend Overview</h2>
          <p class="text-xs text-slate-500">Open tickets and SLA breaches by day</p>
          @if (! empty($managerOverview['department_code']) || ! empty($managerOverview['department_description']))
            <p class="text-xs text-slate-500">
              {{ $managerOverview['department_code'] ?? 'OPS' }}:
              {{ $managerOverview['department_description'] ?? 'Operational performance profile.' }}
            </p>
          @endif
        </div>
      </header>

      @if ($managerOverview && collect($managerOverview['series_by_scale'] ?? [])->flatten(1)->isNotEmpty())
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-4 space-y-4" x-data="{ activeScale: '{{ $managerOverview['active_scale'] ?? '7d' }}' }">
          <div class="flex items-center justify-between px-1">
            <div>
              <h3 class="text-sm font-semibold text-slate-900">Department Operational Performance</h3>
              <p class="text-[11px] text-slate-500">Click any point area to open Tasks for that time window.</p>
            </div>
            <div class="inline-flex rounded-lg border border-slate-300 bg-white p-1 text-xs">
              @foreach (['7d' => 'Last 7 days', '24h' => 'Last 24 hours', '3h' => 'Last 3 hours'] as $scaleKey => $scaleLabel)
                <button
                  type="button"
                  x-on:click="activeScale = '{{ $scaleKey }}'"
                  x-bind:class="activeScale === '{{ $scaleKey }}' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
                  class="rounded-md px-2 py-1"
                >
                  {{ $scaleLabel }}
                </button>
              @endforeach
            </div>
          </div>

          @foreach (['7d', '24h', '3h'] as $managerScaleKey)
            @php
              $series = collect($managerOverview['series_by_scale'][$managerScaleKey] ?? [])->values();
              $maxProd = max(55.0, (float) $series->max('productivity') + 8.0, (float) ($managerOverview['target_productivity'] ?? 42) + 8.0);
              $minProd = max(0.0, (float) floor(($series->min('productivity') ?? 0) - 8.0));
              $maxQuality = 100.0;
              $minQuality = max(70.0, (float) floor(($series->min('quality') ?? 80) - 8.0));
              $width = 640;
              $height = 230;
              $paddingX = 42;
              $paddingTop = 18;
              $paddingBottom = 26;
              $plotWidth = $width - ($paddingX * 2);
              $plotHeight = $height - ($paddingTop + $paddingBottom);
              $count = max(1, $series->count());
              $stepX = $count > 1 ? ($plotWidth / ($count - 1)) : 0;

              $coords = function ($key, $minY, $maxY) use ($series, $paddingX, $paddingTop, $plotHeight, $stepX) {
                  $range = max(1, $maxY - $minY);
                  return $series->values()->map(function ($point, $index) use ($key, $range, $minY, $paddingX, $paddingTop, $plotHeight, $stepX) {
                      $x = $paddingX + ($index * $stepX);
                      $normalized = ((float) $point[$key] - $minY) / $range;
                      $y = $paddingTop + ($plotHeight - ($normalized * $plotHeight));
                      return number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');
                  })->implode(' ');
              };

              $prodRange = max(1, $maxProd - $minProd);
              $qualityRange = max(1, $maxQuality - $minQuality);
              $prodTargetY = $paddingTop + ($plotHeight - ((((float) ($managerOverview['target_productivity'] ?? 42) - $minProd) / $prodRange) * $plotHeight));
              $qualityTargetY = $paddingTop + ($plotHeight - ((((float) ($managerOverview['target_quality'] ?? 95) - $minQuality) / $qualityRange) * $plotHeight));
            @endphp
          <div x-cloak x-show="activeScale === '{{ $managerScaleKey }}'">
          <div class="grid gap-4 lg:grid-cols-2">
            <div
              class="rounded-lg border border-slate-200 bg-white p-3"
              x-data="{
                active: null,
                setActive(event, payload) {
                  const rect = this.$el.getBoundingClientRect();
                  const tooltipWidth = 164;
                  const edgePad = 10;
                  let left = event.clientX - rect.left;
                  const minLeft = edgePad + (tooltipWidth / 2);
                  const maxLeft = rect.width - edgePad - (tooltipWidth / 2);
                  if (rect.width <= (tooltipWidth + (edgePad * 2))) {
                    left = rect.width / 2;
                  } else {
                    left = Math.min(Math.max(left, minLeft), maxLeft);
                  }
                  this.active = { ...payload, left, top: Math.max(18, event.clientY - rect.top - 10) };
                }
              }"
              x-on:mouseleave="active = null"
            >
              <div class="mb-2 flex items-center justify-between">
                <p class="text-sm font-semibold text-blue-700">Productivity (units/hr)</p>
                <p class="text-xs text-slate-500">Target: {{ number_format((float) ($managerOverview['target_productivity'] ?? 42), 1) }}</p>
              </div>
              <div class="relative">
                <svg viewBox="0 0 {{ $width }} {{ $height }}" class="w-full">
                  @foreach ([0, 0.25, 0.5, 0.75, 1] as $gridStep)
                    @php
                      $gridY = $paddingTop + ($plotHeight * $gridStep);
                      $tickValue = number_format($maxProd - (($maxProd - $minProd) * $gridStep), 1);
                    @endphp
                    <line x1="{{ $paddingX }}" y1="{{ number_format($gridY, 2, '.', '') }}" x2="{{ $paddingX + $plotWidth }}" y2="{{ number_format($gridY, 2, '.', '') }}" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="3 3" />
                    <text x="{{ $paddingX - 32 }}" y="{{ number_format($gridY + 3, 2, '.', '') }}" fill="#64748b" font-size="10">{{ $tickValue }}</text>
                  @endforeach
                  <line x1="{{ $paddingX }}" y1="{{ $paddingTop }}" x2="{{ $paddingX }}" y2="{{ $paddingTop + $plotHeight }}" stroke="#cbd5e1" stroke-width="1" />
                  <line x1="{{ $paddingX }}" y1="{{ $paddingTop + $plotHeight }}" x2="{{ $paddingX + $plotWidth }}" y2="{{ $paddingTop + $plotHeight }}" stroke="#cbd5e1" stroke-width="1" />
                  <line x1="{{ $paddingX }}" y1="{{ number_format($prodTargetY, 2, '.', '') }}" x2="{{ $paddingX + $plotWidth }}" y2="{{ number_format($prodTargetY, 2, '.', '') }}" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4 4" />
                  <text x="{{ $paddingX + 6 }}" y="{{ number_format($prodTargetY - 4, 2, '.', '') }}" fill="#b45309" font-size="10">Target</text>
                  <polyline points="{{ $coords('productivity', $minProd, $maxProd) }}" fill="none" stroke="#2563eb" stroke-width="2.2" style="stroke-dasharray:1000;stroke-dashoffset:1000;animation:chart-draw 1.2s ease forwards;" />
                  @foreach ($series as $index => $point)
                    @php
                      $x = $paddingX + ($index * $stepX);
                      $y = $paddingTop + ($plotHeight - (((($point['productivity'] ?? 0) - $minProd) / $prodRange) * $plotHeight));
                      $hoverWidth = $count > 1 ? max(18, ($plotWidth / $count)) : $plotWidth;
                      $hoverX = $count > 1 ? ($x - ($hoverWidth / 2)) : $paddingX;
                      $pointQuery = array_filter([
                          'department_id' => $managerOverview['department_id'] ?? null,
                          'from_date' => $point['from_date'] ?? null,
                          'to_date' => $point['to_date'] ?? null,
                      ], fn ($value) => $value !== null && $value !== '');
                      $pointHref = route('tickets.index', $pointQuery);
                    @endphp
                    <rect
                      x="{{ number_format($hoverX, 2, '.', '') }}"
                      y="{{ number_format($paddingTop, 2, '.', '') }}"
                      width="{{ number_format($hoverWidth, 2, '.', '') }}"
                      height="{{ number_format($plotHeight, 2, '.', '') }}"
                      fill="transparent"
                      class="cursor-pointer"
                      x-on:click="window.location.href='{{ $pointHref }}'"
                      x-on:mouseenter="setActive($event, {label: '{{ $point['label'] }}', value: '{{ number_format((float) $point['productivity'], 1) }}', unit: 'units/hr'})"
                      x-on:mousemove="setActive($event, {label: '{{ $point['label'] }}', value: '{{ number_format((float) $point['productivity'], 1) }}', unit: 'units/hr'})"
                    />
                    <circle cx="{{ number_format($x, 2, '.', '') }}" cy="{{ number_format($y, 2, '.', '') }}" r="3" fill="#2563eb" style="opacity:0;transform-origin:center;pointer-events:none;animation:chart-pop .22s ease {{ number_format($index * 0.02, 2, '.', '') }}s forwards"></circle>
                    <text x="{{ number_format($x - 12, 2, '.', '') }}" y="{{ $height - 6 }}" fill="#64748b" font-size="10">{{ $point['label'] }}</text>
                  @endforeach
                </svg>
                <div
                  x-show="active"
                  x-cloak
                  class="pointer-events-none absolute z-20 w-40 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs shadow"
                  :style="`left: ${active.left}px; top: ${active.top}px; transform: translate(-50%, -100%);`"
                >
                  <p class="font-semibold text-slate-900" x-text="active?.label"></p>
                  <p class="text-blue-700"><span x-text="active?.value"></span> <span x-text="active?.unit"></span></p>
                </div>
              </div>
            </div>

            <div
              class="rounded-lg border border-slate-200 bg-white p-3"
              x-data="{
                active: null,
                setActive(event, payload) {
                  const rect = this.$el.getBoundingClientRect();
                  const tooltipWidth = 164;
                  const edgePad = 10;
                  let left = event.clientX - rect.left;
                  const minLeft = edgePad + (tooltipWidth / 2);
                  const maxLeft = rect.width - edgePad - (tooltipWidth / 2);
                  if (rect.width <= (tooltipWidth + (edgePad * 2))) {
                    left = rect.width / 2;
                  } else {
                    left = Math.min(Math.max(left, minLeft), maxLeft);
                  }
                  this.active = { ...payload, left, top: Math.max(18, event.clientY - rect.top - 10) };
                }
              }"
              x-on:mouseleave="active = null"
            >
              <div class="mb-2 flex items-center justify-between">
                <p class="text-sm font-semibold text-emerald-700">Quality (%)</p>
                <p class="text-xs text-slate-500">Target: {{ number_format((float) ($managerOverview['target_quality'] ?? 95), 1) }}</p>
              </div>
              <div class="relative">
                <svg viewBox="0 0 {{ $width }} {{ $height }}" class="w-full">
                  @foreach ([0, 0.25, 0.5, 0.75, 1] as $gridStep)
                    @php
                      $gridY = $paddingTop + ($plotHeight * $gridStep);
                      $tickValue = number_format($maxQuality - (($maxQuality - $minQuality) * $gridStep), 1);
                    @endphp
                    <line x1="{{ $paddingX }}" y1="{{ number_format($gridY, 2, '.', '') }}" x2="{{ $paddingX + $plotWidth }}" y2="{{ number_format($gridY, 2, '.', '') }}" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="3 3" />
                    <text x="{{ $paddingX - 32 }}" y="{{ number_format($gridY + 3, 2, '.', '') }}" fill="#64748b" font-size="10">{{ $tickValue }}</text>
                  @endforeach
                  <line x1="{{ $paddingX }}" y1="{{ $paddingTop }}" x2="{{ $paddingX }}" y2="{{ $paddingTop + $plotHeight }}" stroke="#cbd5e1" stroke-width="1" />
                  <line x1="{{ $paddingX }}" y1="{{ $paddingTop + $plotHeight }}" x2="{{ $paddingX + $plotWidth }}" y2="{{ $paddingTop + $plotHeight }}" stroke="#cbd5e1" stroke-width="1" />
                  <line x1="{{ $paddingX }}" y1="{{ number_format($qualityTargetY, 2, '.', '') }}" x2="{{ $paddingX + $plotWidth }}" y2="{{ number_format($qualityTargetY, 2, '.', '') }}" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4 4" />
                  <text x="{{ $paddingX + 6 }}" y="{{ number_format($qualityTargetY - 4, 2, '.', '') }}" fill="#b45309" font-size="10">Target</text>
                  <polyline points="{{ $coords('quality', $minQuality, $maxQuality) }}" fill="none" stroke="#10b981" stroke-width="2.2" style="stroke-dasharray:1000;stroke-dashoffset:1000;animation:chart-draw 1.2s ease .15s forwards;" />
                  @foreach ($series as $index => $point)
                    @php
                      $x = $paddingX + ($index * $stepX);
                      $y = $paddingTop + ($plotHeight - (((($point['quality'] ?? 0) - $minQuality) / $qualityRange) * $plotHeight));
                      $hoverWidth = $count > 1 ? max(18, ($plotWidth / $count)) : $plotWidth;
                      $hoverX = $count > 1 ? ($x - ($hoverWidth / 2)) : $paddingX;
                      $pointQuery = array_filter([
                          'department_id' => $managerOverview['department_id'] ?? null,
                          'from_date' => $point['from_date'] ?? null,
                          'to_date' => $point['to_date'] ?? null,
                      ], fn ($value) => $value !== null && $value !== '');
                      $pointHref = route('tickets.index', $pointQuery);
                    @endphp
                    <rect
                      x="{{ number_format($hoverX, 2, '.', '') }}"
                      y="{{ number_format($paddingTop, 2, '.', '') }}"
                      width="{{ number_format($hoverWidth, 2, '.', '') }}"
                      height="{{ number_format($plotHeight, 2, '.', '') }}"
                      fill="transparent"
                      class="cursor-pointer"
                      x-on:click="window.location.href='{{ $pointHref }}'"
                      x-on:mouseenter="setActive($event, {label: '{{ $point['label'] }}', value: '{{ number_format((float) $point['quality'], 1) }}', unit: '%'})"
                      x-on:mousemove="setActive($event, {label: '{{ $point['label'] }}', value: '{{ number_format((float) $point['quality'], 1) }}', unit: '%'})"
                    />
                    <circle cx="{{ number_format($x, 2, '.', '') }}" cy="{{ number_format($y, 2, '.', '') }}" r="3" fill="#10b981" style="opacity:0;transform-origin:center;pointer-events:none;animation:chart-pop .22s ease {{ number_format(($index * 0.02) + 0.08, 2, '.', '') }}s forwards"></circle>
                    <text x="{{ number_format($x - 12, 2, '.', '') }}" y="{{ $height - 6 }}" fill="#64748b" font-size="10">{{ $point['label'] }}</text>
                  @endforeach
                </svg>
                <div
                  x-show="active"
                  x-cloak
                  class="pointer-events-none absolute z-20 w-40 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs shadow"
                  :style="`left: ${active.left}px; top: ${active.top}px; transform: translate(-50%, -100%);`"
                >
                  <p class="font-semibold text-slate-900" x-text="active?.label"></p>
                  <p class="text-emerald-700"><span x-text="active?.value"></span><span x-text="active?.unit"></span></p>
                </div>
              </div>
            </div>
          </div>
          </div>
          @endforeach
        </div>

        @if ($managerHealthSummary)
          <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-sm text-emerald-900">
              <strong>Team Performance:</strong>
              Department is operating at {{ number_format((float) $managerHealthSummary['performance_pct'], 1) }}% of productivity target, and quality is {{ number_format((float) $managerHealthSummary['quality_score'], 1) }}%.
            </p>
          </div>
        @endif
      @endif

      <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-4">
        <div class="mb-2 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-slate-900">Daily Breach Drilldown</h3>
          <div class="text-right">
            <p class="text-xs text-slate-500">Ticket-derived trend. Click a day to open breached tickets.</p>
            <p class="text-xs font-semibold text-slate-700">{{ $breachTotal }} total breach{{ $breachTotal === 1 ? '' : 'es' }} (7d)</p>
          </div>
        </div>
        @if ($useCompactDrilldown)
          <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
            @foreach ($drilldownSeries as $point)
              @php
                $breachQuery = array_filter([
                    'breached' => 1,
                    'department_id' => $managerOverview['department_id'] ?? null,
                    'from_date' => $point['metric_date']?->toDateString(),
                    'to_date' => $point['metric_date']?->toDateString(),
                ], fn ($value) => $value !== null && $value !== '');
                $breaches = (int) ($point['breached_tickets'] ?? 0);
                $dayTone = $breaches > 0 ? 'border-rose-200 bg-rose-50' : 'border-slate-200 bg-white';
              @endphp
              <a href="{{ route('tickets.index', $breachQuery) }}" class="rounded-lg border px-3 py-2 text-sm transition hover:border-blue-300 hover:bg-blue-50 {{ $dayTone }}">
                <p class="text-xs text-slate-500">{{ $point['metric_date']?->format('M j') }}</p>
                <p class="mt-1 font-semibold {{ $breaches > 0 ? 'text-rose-700' : 'text-slate-700' }}">
                  {{ $breaches }} breach{{ $breaches === 1 ? '' : 'es' }}
                </p>
              </a>
            @endforeach
          </div>
        @else
          <div class="h-32">
            <div class="flex h-full items-end gap-2">
              @foreach ($drilldownSeries as $point)
                @php
                  $height = (int) round((($point['breached_tickets'] ?? 0) / $managerMaxBreaches) * 100);
                  $breachQuery = array_filter([
                      'breached' => 1,
                      'department_id' => $managerOverview['department_id'] ?? null,
                      'from_date' => $point['metric_date']?->toDateString(),
                      'to_date' => $point['metric_date']?->toDateString(),
                  ], fn ($value) => $value !== null && $value !== '');
                @endphp
                <div class="flex flex-1 flex-col items-center justify-end gap-1">
                  <a href="{{ route('tickets.index', $breachQuery) }}" class="w-full rounded-t-md bg-rose-500 transition hover:bg-rose-600" style="height: {{ max(8, $height) }}%;" title="Open breached tickets for {{ $point['metric_date']?->format('M j') }}"></a>
                  <span class="text-[10px] text-slate-500">{{ $point['metric_date']?->format('M j') }}</span>
                  <a href="{{ route('tickets.index', $breachQuery) }}" class="text-[10px] font-semibold text-slate-700 hover:text-blue-700 hover:underline">
                    {{ (int) ($point['breached_tickets'] ?? 0) }} breach{{ ((int) ($point['breached_tickets'] ?? 0)) === 1 ? '' : 'es' }}
                  </a>
                </div>
              @endforeach
            </div>
          </div>
        </div>
        @endif
      </div>

      <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-sm font-semibold text-slate-900">Ticket Type Breakdown</h3>
          <p class="text-xs text-slate-500">Active open tickets</p>
        </div>
        @if ($managerTicketTypeBreakdown->isEmpty())
          <p class="text-sm text-slate-500">No active open tickets right now.</p>
        @else
          <div class="space-y-2">
            @foreach ($managerTicketTypeBreakdown as $typeRow)
              @php
                $windowQuery = array_filter([
                    'department_id' => $managerOverview['department_id'] ?? null,
                    'category_id' => $typeRow['category_id'] ?? null,
                ], fn ($value) => $value !== null && $value !== '');

                $breachedTypeQuery = array_merge($windowQuery, ['breached' => 1]);
              @endphp
              <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                <div>
                  <p class="font-medium text-slate-900">{{ $typeRow['category_name'] }}</p>
                  <p class="text-xs text-slate-500">{{ $typeRow['total'] }} total · {{ $typeRow['breached'] }} breached</p>
                </div>
                <div class="flex items-center gap-2">
                  <a href="{{ route('tickets.index', $windowQuery) }}" class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200">
                    View all
                  </a>
                  <a href="{{ route('tickets.index', $breachedTypeQuery) }}" class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 text-xs font-medium text-rose-700 hover:bg-rose-200">
                    Breaches
                  </a>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>

      @if ($managerSlaHealth)
        <div
          class="relative rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-4"
          x-data="{
            activeSla: null,
            setSla(event, payload) {
              const rect = this.$el.getBoundingClientRect();
              const tooltipWidth = 240;
              const edgePad = 12;
              let left = event.clientX - rect.left;
              const minLeft = edgePad + (tooltipWidth / 2);
              const maxLeft = rect.width - edgePad - (tooltipWidth / 2);
              if (rect.width <= (tooltipWidth + (edgePad * 2))) {
                left = rect.width / 2;
              } else {
                left = Math.min(Math.max(left, minLeft), maxLeft);
              }
              this.activeSla = { ...payload, left, top: Math.max(20, event.clientY - rect.top - 10) };
            }
          }"
          x-on:mouseleave="activeSla = null"
        >
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-slate-900">SLA Health</h3>
            <p class="text-xs text-slate-500">
              Current open-ticket distribution (real-time snapshot) · {{ (int) ($managerSlaHealth['open_tickets'] ?? 0) }} open
            </p>
          </div>
          <div class="grid gap-4 md:grid-cols-[220px_1fr] md:items-center">
            @php
              $within = (float) $managerSlaHealth['within'];
              $atRisk = (float) $managerSlaHealth['at_risk'];
              $breached = (float) $managerSlaHealth['breached'];
              $degWithin = round(($within / 100) * 360, 2);
              $degAtRisk = round(($atRisk / 100) * 360, 2);
            @endphp
            <div
              class="mx-auto h-40 w-40 rounded-full"
              style="background:
              conic-gradient(
                #10b981 0deg {{ $degWithin }}deg,
                #f59e0b {{ $degWithin }}deg {{ $degWithin + $degAtRisk }}deg,
                #ef4444 {{ $degWithin + $degAtRisk }}deg 360deg
              );"
              x-on:mouseenter="setSla($event, {label: 'SLA Mix', value: 'Within {{ number_format($within, 1) }}% | At Risk {{ number_format($atRisk, 1) }}% | Breached {{ number_format($breached, 1) }}%', hint: 'Current SLA distribution'})"
              x-on:mousemove="setSla($event, {label: 'SLA Mix', value: 'Within {{ number_format($within, 1) }}% | At Risk {{ number_format($atRisk, 1) }}% | Breached {{ number_format($breached, 1) }}%', hint: 'Current SLA distribution'})"
            >
              <div class="m-8 flex h-24 w-24 items-center justify-center rounded-full bg-white text-center">
                <div>
                  <p class="text-[11px] text-slate-500">Within SLA</p>
                  <p class="text-lg font-semibold text-slate-900">{{ number_format($within, 0) }}%</p>
                </div>
              </div>
            </div>
            <div class="space-y-2 text-sm">
              <div class="flex items-center justify-between rounded-md px-2 py-1 hover:bg-emerald-50" x-on:mouseenter="setSla($event, {label: 'Within SLA', value: '{{ number_format($within, 1) }}%', hint: 'Tickets operating within SLA threshold'})" x-on:mousemove="setSla($event, {label: 'Within SLA', value: '{{ number_format($within, 1) }}%', hint: 'Tickets operating within SLA threshold'})">
                <span class="inline-flex items-center gap-2 text-slate-700"><span class="h-3 w-3 rounded-full bg-emerald-500"></span>Within SLA</span>
                <span class="font-semibold text-slate-900">{{ (int) ($managerSlaHealth['within_count'] ?? 0) }} · {{ number_format($within, 1) }}%</span>
              </div>
              <div class="flex items-center justify-between rounded-md px-2 py-1 hover:bg-amber-50" x-on:mouseenter="setSla($event, {label: 'At Risk', value: '{{ number_format($atRisk, 1) }}%', hint: 'Tickets close to SLA threshold'})" x-on:mousemove="setSla($event, {label: 'At Risk', value: '{{ number_format($atRisk, 1) }}%', hint: 'Tickets close to SLA threshold'})">
                <span class="inline-flex items-center gap-2 text-slate-700"><span class="h-3 w-3 rounded-full bg-amber-500"></span>At Risk</span>
                <span class="font-semibold text-slate-900">{{ (int) ($managerSlaHealth['at_risk_count'] ?? 0) }} · {{ number_format($atRisk, 1) }}%</span>
              </div>
              <div class="flex items-center justify-between rounded-md px-2 py-1 hover:bg-red-50" x-on:mouseenter="setSla($event, {label: 'Breached', value: '{{ number_format($breached, 1) }}%', hint: 'Tickets currently outside SLA'})" x-on:mousemove="setSla($event, {label: 'Breached', value: '{{ number_format($breached, 1) }}%', hint: 'Tickets currently outside SLA'})">
                <span class="inline-flex items-center gap-2 text-slate-700"><span class="h-3 w-3 rounded-full bg-red-500"></span>Breached</span>
                <span class="font-semibold text-slate-900">{{ (int) ($managerSlaHealth['breached_count'] ?? 0) }} · {{ number_format($breached, 1) }}%</span>
              </div>
            </div>
          </div>
          <div
            x-show="activeSla"
            x-cloak
            class="pointer-events-none absolute z-20 w-60 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs shadow-sm"
            :style="`left: ${activeSla.left}px; top: ${activeSla.top}px; transform: translate(-50%, -100%);`"
          >
            <p class="font-semibold text-slate-900" x-text="activeSla?.label"></p>
            <p class="text-slate-700" x-text="activeSla?.value"></p>
            <p class="text-slate-500" x-text="activeSla?.hint"></p>
          </div>
        </div>
      @endif

      @if ($managerBenchmark)
        @php
          $resolution = $managerBenchmark['resolution'] ?? null;

          $resolutionCompanyDelta = ($resolution && !empty($resolution['company']) && !empty($resolution['department']))
              ? round((($resolution['company'] - $resolution['department']) / $resolution['company']) * 100, 1)
              : null;
          $resolutionIndustryDelta = ($resolution && !empty($resolution['industry']) && !empty($resolution['department']))
              ? round((($resolution['industry'] - $resolution['department']) / $resolution['industry']) * 100, 1)
              : null;

          $resolutionCompanyVerb = ($resolutionCompanyDelta ?? 0) >= 0 ? 'better than' : 'behind';
          $resolutionIndustryVerb = ($resolutionIndustryDelta ?? 0) >= 0 ? 'better than' : 'behind';
        @endphp
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">Benchmark Comparison</h3>
            <span class="text-xs text-slate-500">Resolution performance</span>
          </div>
          <div class="grid gap-3">
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Avg Resolution (hours)</p>
              <p class="mt-1 text-sm text-blue-900">
                Dept {{ number_format((float) ($resolution['department'] ?? 0), 1) }} | Company {{ number_format((float) ($resolution['company'] ?? 0), 1) }} | Industry {{ number_format((float) ($resolution['industry'] ?? 0), 1) }}
              </p>
              <p class="mt-1 text-xs text-blue-700">
                {{ abs($resolutionCompanyDelta ?? 0) }}% {{ $resolutionCompanyVerb }} company, {{ abs($resolutionIndustryDelta ?? 0) }}% {{ $resolutionIndustryVerb }} industry
              </p>
            </div>
          </div>
        </div>
      @endif
    </section>
  @endif
@endauth
