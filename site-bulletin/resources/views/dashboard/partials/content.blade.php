@php
    $announcementCount = $announcements->count();
    $categoryCount = $categories->count();
    $linkCount = $categories->flatMap(fn($category) => $category->links)->count();
    $snapshotCount = $snapshots->count();
    $messagePreview = $messagePreview ?? collect();
    $unreadConversationCount = $unreadConversationCount ?? 0;
    $isEmployeeDashboard = auth()->check() && auth()->user()->isEmployee() && ! empty($employeeWorkToday);

    $categoryIconSvg = function ($categoryName) {
        $name = \Illuminate\Support\Str::of((string) $categoryName)->lower();

        if ($name->contains('hot topics')) {
            return '<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M11.25 4.5a.75.75 0 011.28-.53l3.75 3.75a.75.75 0 01.22.53v3.5a.75.75 0 01-.22.53l-3.75 3.75a.75.75 0 01-1.28-.53v-2.2l-4.13-.83a1.75 1.75 0 01-1.37-1.71V9.24c0-.83.58-1.55 1.39-1.72l4.11-.81V4.5z" /><path d="M4.75 8.75A1.75 1.75 0 003 10.5v.5a1.75 1.75 0 001.75 1.75h.75v-4h-.75z" /></svg>';
        }

        if ($name->contains('vacancies')) {
            return '<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7 4.75A1.75 1.75 0 018.75 3h2.5A1.75 1.75 0 0113 4.75V6h2.25A1.75 1.75 0 0117 7.75v5.5A1.75 1.75 0 0115.25 15H4.75A1.75 1.75 0 013 13.25v-5.5A1.75 1.75 0 014.75 6H7V4.75zM8.5 6h3V4.75a.25.25 0 00-.25-.25h-2.5a.25.25 0 00-.25.25V6z" /></svg>';
        }

        if ($name->contains('my site')) {
            return '<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M4.75 2.5A1.75 1.75 0 003 4.25v11.5C3 16.44 3.56 17 4.25 17h11.5c.69 0 1.25-.56 1.25-1.25v-8.5a.75.75 0 00-.22-.53l-3.5-3.5A.75.75 0 0012.75 3h-8zM6 6.25A.75.75 0 016.75 5.5h1.5a.75.75 0 010 1.5h-1.5A.75.75 0 016 6.25zm0 3A.75.75 0 016.75 8.5h1.5a.75.75 0 010 1.5h-1.5A.75.75 0 016 9.25zm0 3a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5A.75.75 0 016 12.25zm5-6a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5A.75.75 0 0111 6.25zm0 3a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5a.75.75 0 01-.75-.75zm0 3a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5a.75.75 0 01-.75-.75z" /></svg>';
        }

        if ($name->contains('diversity') || $name->contains('equity') || $name->contains('inclusion')) {
            return '<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 2.5a.75.75 0 01.72.54l1.02 3.32 3.32 1.02a.75.75 0 010 1.44l-3.32 1.02-1.02 3.32a.75.75 0 01-1.44 0l-1.02-3.32-3.32-1.02a.75.75 0 010-1.44l3.32-1.02 1.02-3.32A.75.75 0 0110 2.5z" /><path d="M15.5 12.5a.75.75 0 01.72.54l.34 1.1 1.1.34a.75.75 0 010 1.44l-1.1.34-.34 1.1a.75.75 0 01-1.44 0l-.34-1.1-1.1-.34a.75.75 0 010-1.44l1.1-.34.34-1.1a.75.75 0 01.72-.54zM4.5 12.75a.75.75 0 01.72.54l.2.65.65.2a.75.75 0 010 1.44l-.65.2-.2.65a.75.75 0 01-1.44 0l-.2-.65-.65-.2a.75.75 0 010-1.44l.65-.2.2-.65a.75.75 0 01.72-.54z" /></svg>';
        }

        if ($name->contains('site tools')) {
            return '<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M11.62 2.96a4.5 4.5 0 00-5.67 5.67L2.72 11.87a1.75 1.75 0 102.47 2.47l3.24-3.23a4.5 4.5 0 005.67-5.67l-2.12 2.12a1.5 1.5 0 11-2.12-2.12l2.12-2.12z" /></svg>';
        }

        if ($name->contains('pxt') || $name->contains('hr')) {
            return '<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.5 8.25a2.75 2.75 0 100-5.5 2.75 2.75 0 000 5.5zM13.5 9.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5zM2.5 15.25A3.75 3.75 0 016.25 11.5h.5A3.75 3.75 0 0110.5 15.25V16H2.5v-.75zM11.5 16v-.75c0-1.06-.34-2.04-.91-2.84a3.24 3.24 0 012.16-.81h.5A3.75 3.75 0 0117 15.25V16h-5.5z" /></svg>';
        }

        return '<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.25 4A2.25 2.25 0 002 6.25v7.5A2.25 2.25 0 004.25 16h11.5A2.25 2.25 0 0018 13.75v-7.5A2.25 2.25 0 0015.75 4H4.25zM5.5 8.25A.75.75 0 016.25 7.5h4.5a.75.75 0 010 1.5h-4.5a.75.75 0 01-.75-.75zm0 3a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75z" clip-rule="evenodd" /></svg>';
    };
@endphp

<div class="space-y-10">
  @if (! $isEmployeeDashboard)
    @php
        $isGuestLanding = ! auth()->check();
        $heroClasses = $isGuestLanding
            ? 'border-slate-200 bg-gradient-to-r from-slate-800 via-slate-700 to-blue-800 text-white'
            : 'border-slate-200 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 text-white';
        $heroAccentClasses = $isGuestLanding
            ? 'bg-[radial-gradient(circle_at_top,_rgba(191,219,254,0.24),_transparent_72%)]'
            : 'bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.16),_transparent_72%)]';
    @endphp
    <section class="site-landing-hero relative overflow-hidden rounded-3xl border px-6 py-6 shadow-lg {{ $heroClasses }}">
      @if ($isGuestLanding)
        <div class="pointer-events-none absolute inset-0 opacity-90" aria-hidden="true">
          <svg class="h-full w-full" viewBox="0 0 1200 260" fill="none" preserveAspectRatio="none">
            <defs>
              <linearGradient id="guestWaveStroke" x1="0" y1="0" x2="1" y2="0">
                <stop offset="0%" stop-color="rgba(191,219,254,0.08)" />
                <stop offset="50%" stop-color="rgba(255,255,255,0.22)" />
                <stop offset="100%" stop-color="rgba(125,211,252,0.10)" />
              </linearGradient>
            </defs>

            <g class="landing-wave-slow">
              <path d="M0 172C96 160 152 124 247 128C342 132 406 188 499 188C591 188 651 141 745 136C853 130 911 184 1012 180C1084 177 1144 146 1200 132" stroke="url(#guestWaveStroke)" stroke-width="2.5" stroke-linecap="round"/>
            </g>
            <g class="landing-wave-fast">
              <path d="M0 208C86 221 153 235 241 228C340 220 389 170 486 166C592 161 649 205 752 206C862 206 911 169 1013 160C1089 154 1143 169 1200 180" stroke="rgba(255,255,255,0.18)" stroke-width="1.6" stroke-linecap="round"/>
            </g>

            <g class="landing-dot-cluster landing-dot-cluster-a" fill="rgba(255,255,255,0.32)">
              <circle cx="902" cy="58" r="3.5"/>
              <circle cx="938" cy="79" r="2.5"/>
              <circle cx="972" cy="51" r="2.75"/>
              <circle cx="1011" cy="72" r="2.25"/>
              <circle cx="1052" cy="44" r="3"/>
              <circle cx="1086" cy="68" r="2.5"/>
            </g>

            <g class="landing-dot-cluster landing-dot-cluster-b" fill="rgba(191,219,254,0.28)">
              <circle cx="764" cy="188" r="2.5"/>
              <circle cx="802" cy="172" r="2.25"/>
              <circle cx="842" cy="196" r="3"/>
              <circle cx="881" cy="182" r="2"/>
              <circle cx="918" cy="204" r="2.5"/>
              <circle cx="956" cy="190" r="2.25"/>
            </g>
          </svg>
        </div>
      @endif
      <div class="absolute inset-y-0 right-0 hidden w-1/4 lg:block {{ $heroAccentClasses }}"></div>
      <div class="relative z-10 flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div class="max-w-3xl">
          <p class="text-[11px] font-semibold uppercase tracking-[0.28em] {{ $isGuestLanding ? 'text-blue-100' : 'text-slate-300' }}">
            {{ $isGuestLanding ? 'Operations Portal' : 'Operations Overview' }}
          </p>
          <div class="mt-2 flex flex-col gap-2 xl:flex-row xl:items-end xl:gap-4">
            <h1 class="text-2xl font-semibold tracking-tight text-white sm:text-3xl">
              {{ auth()->check() ? 'Good to see you, ' . auth()->user()->name : 'Welcome to Site Bulletin' }}
            </h1>
            @auth
              <p class="text-sm text-slate-300">
                @if (auth()->user()->hasRole('manager', 'ops_manager', 'hr', 'admin'))
                  Team visibility, queue health, and unread updates in one place.
                @else
                  Site updates, resources, and recent activity in one place.
                @endif
              </p>
            @endauth
          </div>
          @if ($isGuestLanding)
            <p class="mt-3 max-w-2xl text-sm text-slate-100">
              Sign in to check site updates, open quick resources, message your team, and track operational issues in one place.
            </p>
          @else
            <p class="mt-3 max-w-2xl text-sm text-slate-300">
              Start with the highest-priority work, then move into updates, tickets, and performance detail below.
            </p>
          @endif
        </div>
        @auth
          <div class="flex flex-wrap gap-3">
            <a href="{{ route('tickets.index') }}" class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-inset ring-white/25 hover:bg-white hover:text-slate-900">
              <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M9.25 3a.75.75 0 00-1.5 0v2.5H5.25a.75.75 0 000 1.5H7.75V9.5a.75.75 0 001.5 0V7h2.5a.75.75 0 000-1.5H9.25z" />
                <path d="M16.5 5A1.5 1.5 0 0118 6.5v9A1.5 1.5 0 0116.5 17h-13A1.5 1.5 0 012 15.5v-9A1.5 1.5 0 013.5 5h13z" />
              </svg>
              View Tasks
            </a>
            <a href="{{ route('messages.index') }}" class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-inset ring-white/25 hover:bg-white hover:text-slate-900">
              <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M2 5.75A1.75 1.75 0 013.75 4h12.5A1.75 1.75 0 0118 5.75v7.5A1.75 1.75 0 0116.25 15H6.31l-2.78 2.29A.75.75 0 012 16.71V5.75z" />
              </svg>
              Open Messages
            </a>
          </div>
        @endauth
      </div>
    </section>
  @endif

  @include('dashboard.partials.my-work-today', [
      'employeeWorkToday' => $employeeWorkToday ?? null,
  ])

  @auth
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
      <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Performance Weeks</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $snapshotCount }}</p>
        <p class="text-sm text-slate-500">Recently tracked data points</p>
      </div>
    </section>
  @endauth

  @include('dashboard.partials.manager-attention-queue', [
      'managerAttentionQueue' => $managerAttentionQueue ?? null,
  ])

  @include('dashboard.partials.trend-panels', [
      'snapshots' => $snapshots,
      'departmentMetricTrend' => $departmentMetricTrend,
  ])

  @include('dashboard.partials.messages-widget', [
      'messagePreview' => $messagePreview,
      'unreadConversationCount' => $unreadConversationCount,
  ])

  @include('dashboard.partials.governance-widget', [
      'governanceLogs' => $governanceLogs,
  ])

  @include('dashboard.partials.news-widget', [
      'newsAnnouncements' => $newsAnnouncements ?? collect(),
      'unreadAnnouncementCount' => $unreadAnnouncementCount ?? 0,
      'highPriorityAnnouncementCount' => $highPriorityAnnouncementCount ?? 0,
  ])

  <section class="space-y-5">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-slate-900">Announcements</h2>
      <a href="{{ route('announcements.index') }}" class="text-xs font-semibold uppercase tracking-wider text-blue-600 hover:underline">View all</a>
    </div>
    @if ($announcements->isEmpty())
      <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-slate-500">
        Nothing to share yet. Check back soon for site updates.
      </div>
    @else
      <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($announcements as $announcement)
          <article class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md">
            <div class="space-y-3">
              <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-600">
                  News Update
                </span>
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
              <div class="space-y-2">
                <h3 class="text-lg font-semibold leading-tight text-slate-900">
                  <a href="{{ route('announcements.show', $announcement) }}" class="hover:text-blue-700 hover:underline">
                    {{ $announcement->title }}
                  </a>
                </h3>
                <p class="text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit($announcement->body, 180) }}</p>
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
              <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs text-slate-500">
                <span>{{ $announcement->author->name ?? 'System' }} · Updated {{ $announcement->updated_at?->diffForHumans() ?? 'recently' }}</span>
                <a href="{{ route('announcements.show', $announcement) }}" class="font-semibold text-blue-600 group-hover:text-blue-700">
                  Read update
                </a>
              </div>
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
          @php
              $iconSvg = $categoryIconSvg($category->name);
          @endphp
          <div class="flex h-full flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Category</p>
                <h3 class="text-lg font-semibold text-slate-900">{{ $category->name }}</h3>
              </div>
              <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-900/5 text-slate-600">
                {!! $iconSvg !!}
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
