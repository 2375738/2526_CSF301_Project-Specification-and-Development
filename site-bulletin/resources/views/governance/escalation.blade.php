@extends('layouts.app')

@section('content')
  <div class="space-y-6">
    <header class="rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
      <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Governance</p>
      <h1 class="mt-2 text-2xl font-semibold text-slate-900">Escalation Playbook</h1>
      <p class="mt-2 text-sm text-slate-600">
        Step-by-step guidance on how to escalate incidents using tickets and messaging.
      </p>
    </header>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold text-slate-900">Escalation Scenarios</h2>
      <div class="mt-4 grid gap-4 md:grid-cols-2">
        @foreach ($playbook as $scenario => $steps)
          <article class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
            <h3 class="text-base font-semibold text-slate-900">{{ $scenario }}</h3>
            <ol class="mt-2 list-decimal space-y-1 pl-4 text-sm text-slate-600">
              @foreach ($steps as $step)
                <li>{{ $step }}</li>
              @endforeach
            </ol>
          </article>
        @endforeach
      </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold text-slate-900">Ticket Categories & Contacts</h2>
      <div class="mt-4 space-y-3">
        @foreach ($categories as $category)
          <article class="rounded-xl border border-slate-200 px-4 py-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div>
                <h3 class="text-base font-semibold text-slate-900">{{ $category->name }}</h3>
                <p class="text-sm text-slate-600">
                  Audience:
                  @if ($category->department)
                    {{ $category->department->name }}
                  @elseif ($category->audience === 'managers')
                    Managers
                  @else
                    All employees
                  @endif
                </p>
              </div>
              <a href="{{ route('tickets.create', ['category_id' => $category->id]) }}"
                 class="inline-flex items-center rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold text-white hover:bg-blue-700">
                Report Issue
              </a>
            </div>
            <p class="mt-2 text-xs uppercase tracking-wide text-slate-500">Recommended contacts</p>
            <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-600">
              @if ($category->department)
                @foreach ($category->department->managers as $manager)
                  <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 font-medium">
                    {{ $manager->name }}
                  </span>
                @endforeach
              @else
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 font-medium">
                  Any duty manager
                </span>
              @endif
            </div>
          </article>
        @endforeach
      </div>
    </section>
  </div>
@endsection
