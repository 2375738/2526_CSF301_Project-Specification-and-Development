@extends('layouts.app')

@section('content')
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <a href="{{ route('home') }}" class="text-sm font-medium text-blue-600 hover:underline">
        Back to dashboard
      </a>
      <a href="{{ route('tickets.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">
        View tasks
      </a>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900">Knowledge Snippets</h1>
          <p class="mt-1 text-sm text-slate-600">Search short operational answers without waiting for verbal handover.</p>
        </div>
        <a href="{{ route('tickets.create') }}" class="inline-flex items-center rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
          Report issue instead
        </a>
      </div>
      <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
        @if ($search !== '')
          Showing {{ $snippets->count() }} result{{ $snippets->count() === 1 ? '' : 's' }} for <span class="font-semibold text-slate-900">"{{ $search }}"</span>.
        @else
          Search scanner resets, missed punches, transport issues, or other quick operational answers.
        @endif
      </div>
    </div>

    <form method="GET" action="{{ route('knowledge.index') }}" class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
      <div class="flex flex-wrap gap-3">
        <input
          type="search"
          name="q"
          value="{{ $search }}"
          placeholder="Search scanner reset, missed punch, transport, canteen..."
          aria-label="Search knowledge snippets"
          class="min-w-[220px] flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
        />
        <button type="submit" class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
          Search
        </button>
        @if ($search !== '')
          <a href="{{ route('knowledge.index') }}" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Reset
          </a>
        @endif
      </div>
    </form>

    <section class="grid gap-4 xl:grid-cols-2">
      @forelse ($snippets as $snippet)
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-lg font-semibold text-slate-900">{{ $snippet->title }}</h2>
            @if ($snippet->department)
              <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-0.5 text-xs font-medium text-slate-700">
                {{ $snippet->department->name }}
              </span>
            @endif
            @if ($snippet->audience === 'managers')
              <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-0.5 text-xs font-medium text-blue-700">
                Managers Only
              </span>
            @endif
          </div>
          @if ($snippet->summary)
            <p class="mt-2 text-sm font-medium text-slate-700">{{ $snippet->summary }}</p>
          @endif
          <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-relaxed text-slate-700 whitespace-pre-line">
            {{ $snippet->body }}
          </div>
        </article>
      @empty
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-slate-500 xl:col-span-2">
          No snippets matched your search.
        </div>
      @endforelse
    </section>
  </div>
@endsection
