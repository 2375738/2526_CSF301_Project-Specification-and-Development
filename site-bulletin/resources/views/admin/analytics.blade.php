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

    <form method="GET" class="rounded-xl border border-slate-200 bg-white px-6 py-4 shadow-sm flex flex-wrap items-center gap-3 text-sm text-slate-600">
      <label class="flex items-center gap-2">
        <span>Saved view</span>
        <select name="saved_view_id" class="rounded-lg border border-slate-300 px-3 py-1 focus:border-blue-500 focus:ring-blue-500">
          <option value="">Select…</option>
          @foreach ($savedViews as $view)
            <option value="{{ $view->id }}" @selected(optional($activeSavedView)->id === $view->id)>{{ $view->name }}</option>
          @endforeach
        </select>
      </label>
      <label class="flex items-center gap-2">
        <span>Department</span>
        <select name="department_id" class="rounded-lg border border-slate-300 px-3 py-1 focus:border-blue-500 focus:ring-blue-500">
          <option value="" @selected($selectedDepartment === null)>All Departments</option>
          @foreach ($departmentOptions as $id => $name)
            <option value="{{ $id }}" @selected($selectedDepartment === (int) $id)>{{ $name }}</option>
          @endforeach
        </select>
      </label>
      <label class="flex items-center gap-2">
        <span>Trend Range</span>
        <select name="days" class="rounded-lg border border-slate-300 px-3 py-1 focus:border-blue-500 focus:ring-blue-500">
          @foreach ([7, 14, 30] as $range)
            <option value="{{ $range }}" @selected($trendDays === $range)>{{ $range }} days</option>
          @endforeach
        </select>
      </label>
      <button type="submit" class="inline-flex items-center rounded-full bg-slate-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-900">Apply</button>
    </form>

    <form method="POST" action="{{ route('analytics.views.store') }}" class="rounded-xl border border-dashed border-slate-300 bg-white/60 px-6 py-4 shadow-sm flex flex-wrap items-center gap-3 text-sm text-slate-600">
      @csrf
      <label class="flex items-center gap-2">
        <span>Name</span>
        <input type="text" name="name" value="{{ old('name') }}" class="rounded-lg border border-slate-300 px-3 py-1 focus:border-blue-500 focus:ring-blue-500" required>
      </label>
      <label class="flex items-center gap-2">
        <span>Department</span>
        <select name="department_id" class="rounded-lg border border-slate-300 px-3 py-1 focus:border-blue-500 focus:ring-blue-500">
          <option value="">All</option>
          @foreach ($departmentOptions as $id => $name)
            <option value="{{ $id }}" @selected(old('department_id') == $id)>{{ $name }}</option>
          @endforeach
        </select>
      </label>
      <label class="flex items-center gap-2">
        <span>Range</span>
        <select name="days" class="rounded-lg border border-slate-300 px-3 py-1 focus:border-blue-500 focus:ring-blue-500">
          @foreach ([7, 14, 30] as $range)
            <option value="{{ $range }}" @selected(old('days', $trendDays) == $range)>{{ $range }} days</option>
          @endforeach
        </select>
      </label>
      <button type="submit" class="inline-flex items-center rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">Save view</button>
    </form>

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

    <section class="rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
      <h2 class="text-lg font-semibold text-slate-900">SLA Trend</h2>
      <p class="text-xs text-slate-500">Showing {{ $trendDays }} day trend for {{ $selectedDepartment ? ($departmentOptions[$selectedDepartment] ?? 'department') : 'all departments' }}.</p>
      <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="py-2">Date</th>
              <th class="py-2 text-right">Open Tickets</th>
              <th class="py-2 text-right">SLA Breaches</th>
              <th class="py-2 text-right">Messages Sent</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 text-slate-700">
            @forelse ($trendMetrics as $metric)
              <tr>
                <td class="py-2">{{ $metric['date'] }}</td>
                <td class="py-2 text-right font-semibold text-slate-800">{{ $metric['open'] }}</td>
                <td class="py-2 text-right font-semibold {{ $metric['breaches'] > 0 ? 'text-rose-600' : 'text-emerald-700' }}">{{ $metric['breaches'] }}</td>
                <td class="py-2 text-right text-slate-800">{{ $metric['messages'] }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="py-4 text-center text-sm text-slate-500">No metrics recorded yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>
  </div>
@endsection
