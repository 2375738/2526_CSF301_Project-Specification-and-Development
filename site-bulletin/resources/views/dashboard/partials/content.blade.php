@php
    $announcementCount = $announcements->count();
    $categoryCount = $categories->count();
    $linkCount = $categories->flatMap(fn($category) => $category->links)->count();
    $snapshotCount = $snapshots->count();
    $messagePreview = $messagePreview ?? collect();
    $unreadConversationCount = $unreadConversationCount ?? 0;
@endphp

<div class="space-y-10">
  <section class="relative overflow-hidden rounded-3xl border border-slate-900/10 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 px-8 py-10 text-white shadow-xl">
    <div class="absolute inset-y-0 right-0 hidden w-1/3 bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.22),_transparent_70%)] sm:block"></div>
    <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
      <div class="max-w-xl">
        <p class="text-sm uppercase tracking-[0.2em] text-slate-300">Site Bulletin</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
          Welcome back{{ auth()->check() ? ', ' . auth()->user()->name : '' }} to your operations hub
        </h1>
        <p class="mt-3 text-sm text-slate-200">
          Monitor announcements, jump to key resources, and keep an eye on recent performance snapshots in one place.
        </p>
      </div>
      @auth
        <div class="flex flex-wrap gap-3">
          <a href="{{ route('tickets.index') }}" class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-sm font-semibold text-white ring-1 ring-inset ring-white/30 hover:bg-white hover:text-slate-900">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path d="M9.25 3a.75.75 0 00-1.5 0v2.5H5.25a.75.75 0 000 1.5H7.75V9.5a.75.75 0 001.5 0V7h2.5a.75.75 0 000-1.5H9.25z" />
              <path d="M16.5 5A1.5 1.5 0 0118 6.5v9A1.5 1.5 0 0116.5 17h-13A1.5 1.5 0 012 15.5v-9A1.5 1.5 0 013.5 5h13z" />
            </svg>
            View My Tickets
          </a>
          @if (auth()->user()->isEmployee())
            <a href="{{ route('tickets.create') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm hover:bg-slate-100">
              <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M10 3a.75.75 0 01.75.75V9.25h5.5a.75.75 0 010 1.5h-5.5v5.5a.75.75 0 01-1.5 0v-5.5H3.25a.75.75 0 010-1.5h5.5V3.75A.75.75 0 0110 3z" />
              </svg>
              Report an Issue
            </a>
          @endif
          <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-sm font-semibold text-white ring-1 ring-inset ring-white/30 hover:bg-white hover:text-slate-900">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path d="M10 2a4 4 0 110 8 4 4 0 010-8zM4 16a6 6 0 1112 0v1a1 1 0 01-1 1H5a1 1 0 01-1-1v-1z" />
            </svg>
            Edit Profile
          </a>
        </div>
      @endauth
    </div>
  </section>

  <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active Announcements</p>
      <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $announcementCount }}</p>
      <p class="text-sm text-slate-500">{{ $announcementCount === 1 ? 'Update' : 'Updates' }} currently published</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Quick Link Categories</p>
      <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $categoryCount }}</p>
      <p class="text-sm text-slate-500">Organised resource hubs</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Individual Links</p>
      <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $linkCount }}</p>
      <p class="text-sm text-slate-500">Ready-to-open destinations</p>
    </div>
    @auth
      <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Performance Weeks</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $snapshotCount }}</p>
        <p class="text-sm text-slate-500">Recently tracked data points</p>
      </div>
    @endauth
  </section>

  @include('dashboard.partials.messages-widget', [
      'messagePreview' => $messagePreview,
      'unreadConversationCount' => $unreadConversationCount,
  ])

  @include('dashboard.partials.governance-widget', [
      'governanceLogs' => $governanceLogs,
  ])

  @include('dashboard.partials.analytics-widget', [
      'departmentMetricTrend' => $departmentMetricTrend,
  ])

  <section class="space-y-5">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-slate-900">Announcements</h2>
      <span class="text-xs uppercase tracking-wider text-slate-500">Latest first</span>
    </div>
    @if ($announcements->isEmpty())
      <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-slate-500">
        Nothing to share yet. Check back soon for site updates.
      </div>
    @else
      <div class="divide-y divide-slate-200 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @foreach ($announcements as $announcement)
          <article class="flex flex-col gap-3 px-6 py-5 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-2">
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-base font-semibold text-slate-900">{{ $announcement->title }}</h3>
                @if ($announcement->is_pinned)
                  <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-0.5 text-xs font-medium text-amber-700">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <path d="M7.5 2.75A.75.75 0 018.25 2h3.5a.75.75 0 01.75.75v4.257l2.459 1.64a.75.75 0 01-.12 1.32l-2.339.936V15l1.3 1.3a.75.75 0 11-1.06 1.06L10 15.43l-2.44 1.93a.75.75 0 11-1.06-1.06L7.8 15v-4.097l-2.34-.936a.75.75 0 01-.12-1.32L7.5 7.007V2.75z" />
                    </svg>
                    Pinned
                  </span>
                @endif
                @if ($announcement->department)
                  <span class="inline-flex items-center rounded-full bg-slate-900/10 px-3 py-0.5 text-xs font-medium text-slate-700">
                    {{ $announcement->department->name }}
                  </span>
                @endif
                @if ($announcement->audience === 'managers')
                  <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-0.5 text-xs font-medium text-blue-700">
                    Managers Only
                  </span>
                @endif
              </div>
              @if ($announcement->starts_at || $announcement->ends_at)
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                  @if ($announcement->starts_at)
                    {{ $announcement->starts_at->format('M j, H:i') }}
                  @endif
                  —
                  @if ($announcement->ends_at)
                    {{ $announcement->ends_at->format('M j, H:i') }}
                  @else
                    Ongoing
                  @endif
                </p>
              @endif
              @if ($announcement->body)
                <p class="text-sm leading-relaxed text-slate-600">{{ \Illuminate\Support\Str::limit($announcement->body, 260) }}</p>
              @endif
            </div>
            <div class="flex items-center gap-3 text-xs text-slate-400">
              <span>Updated {{ $announcement->updated_at?->diffForHumans() ?? 'recently' }}</span>
            </div>
          </article>
        @endforeach
      </div>
    @endif
  </section>

  <section class="space-y-5">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-slate-900">Quick Links</h2>
      <span class="text-xs uppercase tracking-wider text-slate-500">Open in new tab</span>
    </div>
    @if ($categories->isEmpty())
      <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-slate-500">
        No quick links yet. Managers can add them through the admin panel.
      </div>
    @else
      <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($categories as $category)
          <div class="flex h-full flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Category</p>
                <h3 class="text-lg font-semibold text-slate-900">{{ $category->name }}</h3>
              </div>
              <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-900/5 text-slate-600">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fill-rule="evenodd" d="M3.105 3.553A1.5 1.5 0 014.582 2.5h10.836a1.5 1.5 0 011.477 1.053l1.642 5.132a1.5 1.5 0 01-1.43 1.964H2.893a1.5 1.5 0 01-1.43-1.964l1.642-5.132zM2.5 12.75A1.75 1.75 0 014.25 11h11.5a1.75 1.75 0 011.75 1.75v1.5A2.75 2.75 0 0114.75 17h-9.5A2.75 2.75 0 012.5 15.25v-2.5z" clip-rule="evenodd" />
                </svg>
              </span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              @if ($category->is_sensitive)
                <span class="inline-flex w-fit items-center gap-1 rounded-full bg-rose-100 px-3 py-0.5 text-xs font-semibold text-rose-600">
                  <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-.75-11a.75.75 0 011.5 0v4a.75.75 0 01-1.5 0V7zm.75 6a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
                  </svg>
                  Restricted
                </span>
              @endif
              @if ($category->department)
                <span class="inline-flex w-fit items-center rounded-full bg-slate-900/5 px-3 py-0.5 text-xs font-medium text-slate-600">
                  {{ $category->department->name }}
                </span>
              @elseif ($category->audience === 'managers')
                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-0.5 text-xs font-medium text-blue-700">
                  Managers Only
                </span>
              @endif
            </div>
            @if ($category->audience === 'department' && ! $category->department)
              <span class="inline-flex w-fit items-center gap-1 rounded-full bg-amber-100 px-3 py-0.5 text-xs font-semibold text-amber-700">
                Department Specific
              </span>
            @endif
            @if ($category->links->isEmpty())
              <p class="text-sm text-slate-500">No links for this category yet.</p>
            @else
              <ul class="space-y-2 text-sm">
                @foreach ($category->links as $link)
                  <li>
                    <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="group inline-flex w-full items-center justify-between rounded-lg border border-transparent px-3 py-2 text-slate-700 transition hover:border-slate-200 hover:bg-slate-50">
                      <span class="font-medium group-hover:text-slate-900">{{ $link->label }}</span>
                      <span class="inline-flex items-center gap-2">
                        @if ($link->is_hot)
                          <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-red-600">Hot</span>
                        @endif
                        <svg class="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                          <path fill-rule="evenodd" d="M5.22 14.78a.75.75 0 001.06 0l7.47-7.47v4.19a.75.75 0 001.5 0v-6.5A.75.75 0 0014.5 4h-6.5a.75.75 0 000 1.5h4.19l-7.47 7.47a.75.75 0 000 1.06z" clip-rule="evenodd" />
                        </svg>
                      </span>
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
      <section class="space-y-5">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">Performance (Last 6 Weeks)</h2>
          @if ($riskFlag)
            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
              <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 0 0116 0zm-7.25-4.5a.75.75 0 00-1.5 0v5a.75.75 0 001.5 0v-5zm.25 8.5a1 1 0 10-2 0 1 1 0 002 0z" clip-rule="evenodd" />
              </svg>
              ADAPT Risk
            </span>
          @endif
        </div>
        @if ($snapshots->isEmpty())
          <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-slate-500">
            No performance data yet.
          </div>
        @else
          <div class="grid gap-5 md:grid-cols-3">
            @foreach ($snapshots as $snapshot)
              <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div>
                  <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Week of {{ $snapshot->week_start->format('M j') }}</p>
                  <p class="mt-2 text-2xl font-semibold text-slate-900">
                    {{ $snapshot->units_per_hour ?? '—' }}
                    <span class="text-xs font-normal text-slate-500">units/hr</span>
                  </p>
                </div>
                <div class="flex items-center justify-between text-sm text-slate-600">
                  <span>Percentile</span>
                  <span class="font-semibold">{{ $snapshot->rank_percentile ?? '—' }}</span>
                </div>
                <div class="overflow-hidden rounded-full bg-slate-100">
                  @php
                      $percent = is_numeric($snapshot->rank_percentile ?? null) ? max(0, min(100, $snapshot->rank_percentile)) : null;
                  @endphp
                  <div class="h-2 bg-slate-900 transition-all duration-500" style="width: {{ $percent !== null ? $percent . '%' : '0%' }}"></div>
                </div>
              </div>
            @endforeach
          </div>
        @endif
        <p class="text-xs text-slate-500">
          Placeholder metrics for coursework. Not reflective of live ADAPT performance.
        </p>
      </section>
    @endif
  @endauth
</div>
