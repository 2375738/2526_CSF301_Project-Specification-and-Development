@extends('layouts.app')

@section('content')
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <a href="{{ route('home') }}" class="text-sm font-medium text-blue-600 hover:underline">
        Back to dashboard
      </a>
      <a href="{{ route('tickets.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">
        View tickets
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
          Showing grouped help results for <span class="font-semibold text-slate-900">"{{ $search }}"</span>.
        @else
          Search scanner resets, missed punches, transport issues, updates, or quick links.
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

    @if ($search !== '')
      <section class="grid gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-base font-semibold text-slate-900">Quick links</h2>
          <div class="mt-3 space-y-2">
            @forelse (($quickLinks ?? collect()) as $link)
              <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="block rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm hover:border-blue-200 hover:bg-blue-50">
                <span class="font-semibold text-slate-900">{{ $link->label }}</span>
                <span class="mt-1 block text-xs text-slate-500">{{ $link->category?->name ?? 'Resource' }}</span>
              </a>
            @empty
              <p class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm text-slate-500">No quick links matched.</p>
            @endforelse
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-base font-semibold text-slate-900">Announcements</h2>
          <div class="mt-3 space-y-2">
            @forelse (($announcements ?? collect()) as $announcement)
              <a href="{{ route('announcements.show', $announcement) }}" class="block rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm hover:border-blue-200 hover:bg-blue-50">
                <span class="font-semibold text-slate-900">{{ $announcement->title }}</span>
                <span class="mt-1 block text-xs text-slate-500">{{ ucfirst($announcement->priority) }} priority</span>
              </a>
            @empty
              <p class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm text-slate-500">No announcements matched.</p>
            @endforelse
          </div>
        </div>

        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
          <h2 class="text-base font-semibold text-blue-950">Still need help?</h2>
          <p class="mt-2 text-sm text-blue-900">If the results do not answer the issue, start a guided report and choose the closest path.</p>
          <a href="{{ route('tickets.create', ['guide_area' => 'equipment']) }}" class="mt-4 inline-flex items-center rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            Start guided report
          </a>
        </div>
      </section>
    @endif

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
