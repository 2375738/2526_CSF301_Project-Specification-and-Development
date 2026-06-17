@php
    $roleActions = collect($roleActions ?? []);
    $toneClasses = [
        'amber' => 'border-amber-200 bg-amber-50 text-amber-900',
        'blue' => 'border-blue-200 bg-blue-50 text-blue-900',
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
        'indigo' => 'border-indigo-200 bg-indigo-50 text-indigo-900',
        'orange' => 'border-orange-200 bg-orange-50 text-orange-900',
        'rose' => 'border-rose-200 bg-rose-50 text-rose-900',
        'slate' => 'border-slate-200 bg-slate-50 text-slate-900',
    ];
@endphp

@auth
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-700">Next Actions</p>
        <h2 class="mt-1 text-lg font-semibold text-slate-900">What needs your attention now</h2>
      </div>
      <p class="text-sm text-slate-500">{{ auth()->user()->role?->value ? str(auth()->user()->role->value)->replace('_', ' ')->title() : 'Role' }} worklist</p>
    </div>

    @if ($roleActions->isEmpty())
      <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-6">
        <p class="text-sm font-medium text-slate-700">No urgent actions right now.</p>
        <p class="mt-1 text-sm text-slate-500">Use the dashboard sections below to review updates, messages, tickets, and resources.</p>
      </div>
    @else
      <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($roleActions as $action)
          @php
              $classes = $toneClasses[$action['tone'] ?? 'slate'] ?? $toneClasses['slate'];
          @endphp
          <a href="{{ $action['href'] }}" class="group flex min-h-32 flex-col justify-between rounded-xl border px-4 py-4 transition hover:-translate-y-0.5 hover:shadow-sm {{ $classes }}">
            <div>
              <div class="flex items-center justify-between gap-3">
                <span class="rounded-full bg-white/70 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide">{{ $action['category'] }}</span>
                <svg class="h-4 w-4 opacity-60 transition group-hover:translate-x-0.5 group-hover:opacity-100" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fill-rule="evenodd" d="M5.22 14.78a.75.75 0 001.06 0l7.47-7.47v4.19a.75.75 0 001.5 0v-6.5A.75.75 0 0014.5 4h-6.5a.75.75 0 000 1.5h4.19l-7.47 7.47a.75.75 0 000 1.06z" clip-rule="evenodd" />
                </svg>
              </div>
              <h3 class="mt-3 text-sm font-semibold">{{ $action['title'] }}</h3>
              <p class="mt-1 text-sm opacity-80">{{ $action['detail'] }}</p>
            </div>
            <p class="mt-4 text-xs font-semibold uppercase tracking-wide opacity-70">Open action</p>
          </a>
        @endforeach
      </div>
    @endif
  </section>
@endauth
