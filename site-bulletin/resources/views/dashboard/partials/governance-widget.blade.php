@can('viewAny', \App\Models\AuditLog::class)
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
    <header class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-semibold text-slate-900">Governance Activity</h2>
        <p class="text-xs text-slate-500">
          Recent audit events relevant to your teams.
        </p>
      </div>
      <a href="{{ route('governance.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">
        Governance Hub
      </a>
    </header>

    <ul class="space-y-3">
      @forelse ($governanceLogs as $log)
        <li class="rounded-lg border border-slate-200 px-4 py-3">
          <div class="flex items-center justify-between text-xs text-slate-500">
            <span class="font-semibold text-slate-800">{{ $log->event_type }}</span>
            <span>{{ $log->occurred_at?->diffForHumans() }}</span>
          </div>
          <p class="mt-1 text-sm text-slate-600">
            {{ $log->actor?->name ?? 'System' }}
            @if ($log->payload)
              &middot; {{ json_encode($log->payload) }}
            @endif
          </p>
        </li>
      @empty
        <li class="text-sm text-slate-500">No recent governance activity.</li>
      @endforelse
    </ul>
  </section>
@endcan
