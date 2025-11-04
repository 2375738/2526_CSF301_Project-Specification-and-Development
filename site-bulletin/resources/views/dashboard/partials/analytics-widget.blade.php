@if ($departmentMetricTrend->isNotEmpty())
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
    <header class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-semibold text-slate-900">Department SLA Trend</h2>
        <p class="text-xs text-slate-500">Last {{ $departmentMetricTrend->count() }} days</p>
      </div>
      <a href="{{ route('analytics.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">
        View analytics
      </a>
    </header>
    <table class="w-full text-sm">
      <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
        <tr>
          <th class="py-2">Date</th>
          <th class="py-2 text-right">Open</th>
          <th class="py-2 text-right">Breaches</th>
          <th class="py-2 text-right">Messages</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 text-slate-700">
        @foreach ($departmentMetricTrend as $metric)
          <tr>
            <td class="py-2">{{ $metric->metric_date->format('M j') }}</td>
            <td class="py-2 text-right font-semibold text-slate-800">{{ $metric->open_tickets }}</td>
            <td class="py-2 text-right font-semibold {{ $metric->sla_breaches > 0 ? 'text-rose-600' : 'text-emerald-700' }}">{{ $metric->sla_breaches }}</td>
            <td class="py-2 text-right text-slate-800">{{ $metric->messages_sent }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </section>
@endif
