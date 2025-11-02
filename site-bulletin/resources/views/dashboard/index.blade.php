@extends('layouts.app')

@section('content')
  <div class="space-y-8">
    <section class="bg-white shadow-sm rounded-xl px-6 py-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Site Bulletin</h1>
        <p class="text-sm text-slate-600">
          Stay up-to-date with site announcements, quick links, and ticket activity.
        </p>
      </div>
      @auth
        <div class="flex flex-wrap gap-2">
          <a href="{{ route('tickets.index') }}" class="inline-flex items-center rounded-full bg-slate-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-900">
            View My Tickets
          </a>
          @if (auth()->user()->isEmployee())
            <a href="{{ route('tickets.create') }}" class="inline-flex items-center rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
              Report an Issue
            </a>
          @endif
        </div>
      @endauth
    </section>

    <section>
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-semibold text-slate-900">Announcements</h2>
        <span class="text-xs uppercase tracking-wider text-slate-500">Latest first</span>
      </div>
      @if ($announcements->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-slate-500">
          Nothing to share yet. Check back soon for site updates.
        </div>
      @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          @foreach ($announcements as $announcement)
            <article class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
              <div class="flex items-start justify-between gap-3">
                <h3 class="text-base font-semibold text-slate-900">
                  {{ $announcement->title }}
                </h3>
                @if ($announcement->is_pinned)
                  <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                    Pinned
                  </span>
                @endif
              </div>
              @if ($announcement->starts_at || $announcement->ends_at)
                <p class="mt-1 text-xs text-slate-500">
                  @if ($announcement->starts_at)
                    {{ $announcement->starts_at->format('M j, H:i') }}
                  @endif
                  –
                  @if ($announcement->ends_at)
                    {{ $announcement->ends_at->format('M j, H:i') }}
                  @else
                    Ongoing
                  @endif
                </p>
              @endif
              @if ($announcement->body)
                <p class="mt-3 text-sm leading-relaxed text-slate-700">
                  {{ \Illuminate\Support\Str::limit($announcement->body, 220) }}
                </p>
              @endif
            </article>
          @endforeach
        </div>
      @endif
    </section>

    <section>
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-semibold text-slate-900">Quick Links</h2>
        <span class="text-xs uppercase tracking-wider text-slate-500">Open in new tab</span>
      </div>
      @if ($categories->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-slate-500">
          No quick links yet. Managers can add them through the admin panel.
        </div>
      @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          @foreach ($categories as $category)
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
              <div class="flex items-center justify-between gap-3">
                <h3 class="text-base font-semibold text-slate-900">{{ $category->name }}</h3>
                @if ($category->is_sensitive)
                  <span class="text-xs font-medium text-rose-500">Restricted</span>
                @endif
              </div>
              @if ($category->links->isEmpty())
                <p class="mt-3 text-sm text-slate-500">No links for this category yet.</p>
              @else
                <ul class="mt-3 space-y-2 text-sm">
                  @foreach ($category->links as $link)
                    <li>
                      <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-md px-2 py-1 text-slate-700 hover:bg-slate-100">
                        <span>{{ $link->label }}</span>
                        @if ($link->is_hot)
                          <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-red-600">Hot</span>
                        @endif
                        <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                          <path fill-rule="evenodd" d="M5.22 14.78a.75.75 0 001.06 0l7.47-7.47v4.19a.75.75 0 001.5 0v-6.5A.75.75 0 0014.5 4h-6.5a.75.75 0 000 1.5h4.19l-7.47 7.47a.75.75 0 000 1.06z" clip-rule="evenodd" />
                        </svg>
                      </a>
                    </li>
                  @endforeach
                </ul>
              @endif
            </div>
          @endforeach
        </div>
      @endif
    </section>

    @auth
      @if (auth()->user()->isEmployee())
        <section>
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold text-slate-900">Performance (Last 6 Weeks)</h2>
            @if ($riskFlag)
              <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7.25-4.5a.75.75 0 00-1.5 0v5a.75.75 0 001.5 0v-5zm.25 8.5a1 1 0 10-2 0 1 1 0 002 0z" clip-rule="evenodd" />
                </svg>
                ADAPT Risk
              </span>
            @endif
          </div>
          @if ($snapshots->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-slate-500">
              No performance data yet.
            </div>
          @else
            <div class="grid gap-4 md:grid-cols-3">
              @foreach ($snapshots as $snapshot)
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                  <p class="text-xs uppercase tracking-wide text-slate-500">
                    Week of {{ $snapshot->week_start->format('M j') }}
                  </p>
                  <p class="mt-2 text-lg font-semibold text-slate-900">
                    {{ $snapshot->units_per_hour ?? '—' }} <span class="text-xs font-normal text-slate-500">units/hr</span>
                  </p>
                  <p class="text-sm text-slate-600">
                    Percentile: <span class="font-medium">{{ $snapshot->rank_percentile ?? '—' }}</span>
                  </p>
                </div>
              @endforeach
            </div>
          @endif
          <p class="mt-3 text-xs text-slate-500">
            Placeholder metrics for coursework. Not reflective of live ADAPT performance.
          </p>
        </section>
      @endif
    @endauth
  </div>
@endsection

