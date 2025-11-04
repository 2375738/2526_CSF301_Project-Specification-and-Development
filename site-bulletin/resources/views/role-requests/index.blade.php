@extends('layouts.app')

@section('content')
  <div class="space-y-6">
    <header class="rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
      <div class="flex items-start justify-between gap-4">
        <div>
          <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Governance</p>
          <h1 class="mt-2 text-2xl font-semibold text-slate-900">Role Change Requests</h1>
          <p class="mt-2 text-sm text-slate-600">
            Track submitted and assigned role change approvals.
          </p>
        </div>
        <a href="{{ route('role-requests.create') }}"
           class="inline-flex items-center rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
          New Request
        </a>
      </div>
    </header>

    @if (session('status'))
      <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ session('status') }}
      </div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
            <th class="px-4 py-3">Requested For</th>
            <th class="px-4 py-3">Requested Role</th>
            <th class="px-4 py-3">Department</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Submitted</th>
            <th class="px-4 py-3">Approver Notes</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
          @forelse ($requests as $request)
            <tr>
              <td class="px-4 py-3">
                <div class="flex flex-col">
                  <span class="font-semibold text-slate-900">{{ $request->target?->name ?? 'User removed' }}</span>
                  <span class="text-xs text-slate-500">Submitted by {{ $request->requester?->name ?? 'Unknown' }}</span>
                </div>
              </td>
              <td class="px-4 py-3">
                {{ ucfirst(str_replace('_', ' ', $request->requested_role)) }}
              </td>
              <td class="px-4 py-3">
                {{ $request->department?->name ?? 'Not specified' }}
              </td>
              <td class="px-4 py-3">
                @php
                  $statusClasses = match ($request->status) {
                    \App\Models\RoleChangeRequest::STATUS_APPROVED => 'bg-emerald-100 text-emerald-700',
                    \App\Models\RoleChangeRequest::STATUS_REJECTED => 'bg-rose-100 text-rose-700',
                    default => 'bg-amber-100 text-amber-700',
                  };
                @endphp
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase {{ $statusClasses }}">
                  {{ ucfirst($request->status) }}
                </span>
                @if ($request->approver)
                  <p class="mt-1 text-xs text-slate-500">By {{ $request->approver->name }}</p>
                @endif
              </td>
              <td class="px-4 py-3 text-sm text-slate-600">
                {{ $request->created_at->format('M j, Y H:i') }}
              </td>
              <td class="px-4 py-3 text-sm text-slate-600">
                @if ($request->decision_notes)
                  {{ $request->decision_notes }}
                @else
                  <span class="text-xs text-slate-400">Awaiting decision</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">
                No role change requests yet. Submit one to update access.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
      <div class="mt-4">
        {{ $requests->links() }}
      </div>
    </section>
  </div>
@endsection
