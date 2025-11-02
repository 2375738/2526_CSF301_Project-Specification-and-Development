@extends('layouts.app')

@section('content')
  <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Analytics &amp; Reporting</h1>
        <p class="text-sm text-slate-600">Operational snapshot for the last 30 days. Export data for further analysis.</p>
      </div>
      <a href="{{ route('analytics.export') }}" class="inline-flex items-center rounded-full bg-slate-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-900">Download CSV</a>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      @php $priorities = ['critical' => 'Critical', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low']; @endphp
      @foreach ($priorities as $key => $label)
        <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
          <p class="text-xs uppercase tracking-wide text-slate-500">Open {{ $label }} Tickets</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $openByPriority[$key] ?? 0 }}</p>
        </div>
      @endforeach
      <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">SLA Breaches (7 days)</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $breachesLastWeek }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Avg First Response</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $firstResponseAvg ? number_format($firstResponseAvg, 1) . ' mins' : 'n/a' }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Avg Resolution</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $resolutionAvg ? number_format($resolutionAvg, 1) . ' mins' : 'n/a' }}</p>
      </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <section class="rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Top Categories</h2>
        <table class="mt-4 w-full text-sm">
          <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="py-2">Category</th>
              <th class="py-2 text-right">Tickets</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            @foreach ($topCategories as $row)
              <tr>
                <td class="py-2 text-slate-700">{{ $row->category_name }}</td>
                <td class="py-2 text-right font-semibold text-slate-800">{{ $row->total }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </section>

      <section class="rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Recent Activity</h2>
        <ul class="mt-4 space-y-3 text-sm">
          @foreach ($recentActivity as $ticket)
            <li class="rounded-lg border border-slate-200 px-4 py-3">
              <div class="flex items-center justify-between">
                <p class="font-semibold text-slate-800">#{{ $ticket->id }} · {{ $ticket->title }}</p>
                <span class="text-xs text-slate-500">{{ $ticket->updated_at->diffForHumans() }}</span>
              </div>
              <p class="mt-1 text-xs text-slate-500">Priority {{ ucfirst($ticket->priority->value ?? $ticket->priority) }} · {{ $ticket->category->name ?? 'Uncategorised' }} · Assigned {{ $ticket->assignee->name ?? 'Unassigned' }}</p>
            </li>
          @endforeach
        </ul>
      </section>
    </div>
  </div>
@endsection
