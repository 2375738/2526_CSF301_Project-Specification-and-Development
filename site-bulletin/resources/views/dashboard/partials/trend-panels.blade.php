@php
    $employeeTrend = ($snapshots ?? collect())
        ->sortBy('week_start')
        ->values();

    $employeeMax = max(1, (int) $employeeTrend->max('units_per_hour'));
    $employeeTarget = 42;
    $employeeBestRank = $employeeTrend->min('rank_percentile');
    $employeeLatestRank = $employeeTrend->last()?->rank_percentile;
    $employeeQualityMax = 100;

    $managerTrend = ($departmentMetricTrend ?? collect())->values();
    $managerMaxOpen = max(1, (int) $managerTrend->max('open_tickets'));
    $managerBenchmark = $managerBenchmark ?? null;
@endphp

@auth
  @if (auth()->user()->isEmployee() && $employeeTrend->isNotEmpty())
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
      <header class="flex items-center justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-900">Employee Performance Trend</h2>
          <p class="text-xs text-slate-500">Units per hour by week with target comparison</p>
        </div>
      </header>
      <div class="h-40 rounded-xl border border-slate-200 bg-slate-50 px-3 py-4">
        <div class="flex h-full items-end gap-2">
          @foreach ($employeeTrend as $point)
            @php
              $height = (int) round((($point->units_per_hour ?? 0) / $employeeMax) * 100);
              $isBelowTarget = (int) ($point->units_per_hour ?? 0) < $employeeTarget;
            @endphp
            <div class="flex flex-1 flex-col items-center justify-end gap-2">
              <div class="w-full rounded-t-md {{ $isBelowTarget ? 'bg-amber-500' : 'bg-blue-500' }}" style="height: {{ max(8, $height) }}%;"></div>
              <span class="text-[10px] text-slate-500">{{ $point->week_start?->format('M j') }}</span>
            </div>
          @endforeach
        </div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-semibold text-slate-900">Quality vs Productivity Trend</h3>
          <span class="text-xs text-slate-500">Rank-based quality index</span>
        </div>
        <div class="mt-3 grid gap-2">
          @foreach ($employeeTrend as $point)
            @php
              $qualityIndex = $point->rank_percentile !== null ? max(0, min(100, 100 - (int) $point->rank_percentile)) : 0;
              $productivityWidth = (int) round((($point->units_per_hour ?? 0) / $employeeMax) * 100);
              $qualityWidth = (int) round(($qualityIndex / $employeeQualityMax) * 100);
            @endphp
            <div class="space-y-1">
              <div class="flex items-center justify-between text-[11px] text-slate-500">
                <span>{{ $point->week_start?->format('M j') }}</span>
                <span>{{ $point->units_per_hour ?? 0 }} /hr · QI {{ $qualityIndex }}</span>
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div class="overflow-hidden rounded-full bg-slate-200">
                  <div class="h-2 rounded-full bg-blue-500" style="width: {{ max(4, $productivityWidth) }}%;"></div>
                </div>
                <div class="overflow-hidden rounded-full bg-slate-200">
                  <div class="h-2 rounded-full bg-emerald-500" style="width: {{ max(4, $qualityWidth) }}%;"></div>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
      <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3">
          <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Peak Throughput</p>
          <p class="mt-1 text-2xl font-semibold text-blue-900">{{ $employeeTrend->max('units_per_hour') }} /hr</p>
          <p class="text-xs text-blue-700">Highest week in current trend window</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
          <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Rank Trend</p>
          <p class="mt-1 text-2xl font-semibold text-emerald-900">
            {{ $employeeLatestRank ?? '-' }}%
          </p>
          <p class="text-xs text-emerald-700">
            Lower is better. Best: {{ $employeeBestRank ?? '-' }}%
          </p>
        </div>
      </div>
    </section>
  @endif

  @if (auth()->user()->hasRole('manager', 'ops_manager') && $managerTrend->isNotEmpty())
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
      <header class="flex items-center justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-900">Department Trend Overview</h2>
          <p class="text-xs text-slate-500">Open tickets and SLA breaches by day</p>
        </div>
      </header>
      <div class="h-44 rounded-xl border border-slate-200 bg-slate-50 px-3 py-4">
        <div class="flex h-full items-end gap-2">
          @foreach ($managerTrend as $point)
            @php
              $height = (int) round((($point->open_tickets ?? 0) / $managerMaxOpen) * 100);
            @endphp
            <div class="flex flex-1 flex-col items-center justify-end gap-1">
              <div class="w-full rounded-t-md bg-emerald-500" style="height: {{ max(8, $height) }}%;"></div>
              <span class="text-[10px] text-slate-500">{{ $point->metric_date?->format('M j') }}</span>
              <span class="text-[10px] font-semibold text-slate-700">{{ $point->sla_breaches }} breach{{ $point->sla_breaches === 1 ? '' : 'es' }}</span>
            </div>
          @endforeach
        </div>
      </div>
      @if ($managerBenchmark)
        @php
          $resolution = $managerBenchmark['resolution'] ?? null;
          $sla = $managerBenchmark['sla'] ?? null;

          $resolutionCompanyDelta = ($resolution && !empty($resolution['company']) && !empty($resolution['department']))
              ? round((($resolution['company'] - $resolution['department']) / $resolution['company']) * 100, 1)
              : null;
          $resolutionIndustryDelta = ($resolution && !empty($resolution['industry']) && !empty($resolution['department']))
              ? round((($resolution['industry'] - $resolution['department']) / $resolution['industry']) * 100, 1)
              : null;

          $slaCompanyDelta = ($sla && !empty($sla['company']) && !empty($sla['department']))
              ? round($sla['department'] - $sla['company'], 1)
              : null;
          $slaIndustryDelta = ($sla && !empty($sla['industry']) && !empty($sla['department']))
              ? round($sla['department'] - $sla['industry'], 1)
              : null;
        @endphp
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">Benchmark Comparison</h3>
            <span class="text-xs text-slate-500">Department vs company vs industry</span>
          </div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Avg Resolution (hours)</p>
              <p class="mt-1 text-sm text-blue-900">
                Dept {{ number_format((float) ($resolution['department'] ?? 0), 1) }} | Company {{ number_format((float) ($resolution['company'] ?? 0), 1) }} | Industry {{ number_format((float) ($resolution['industry'] ?? 0), 1) }}
              </p>
              <p class="mt-1 text-xs text-blue-700">
                {{ $resolutionCompanyDelta ?? 0 }}% better than company, {{ $resolutionIndustryDelta ?? 0 }}% better than industry
              </p>
            </div>
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">SLA Adherence (%)</p>
              <p class="mt-1 text-sm text-emerald-900">
                Dept {{ number_format((float) ($sla['department'] ?? 0), 1) }} | Company {{ number_format((float) ($sla['company'] ?? 0), 1) }} | Industry {{ number_format((float) ($sla['industry'] ?? 0), 1) }}
              </p>
              <p class="mt-1 text-xs text-emerald-700">
                +{{ $slaCompanyDelta ?? 0 }} pts vs company, +{{ $slaIndustryDelta ?? 0 }} pts vs industry
              </p>
            </div>
          </div>
        </div>
      @endif
    </section>
  @endif
@endauth
